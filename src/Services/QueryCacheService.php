<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use FilesystemIterator;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Сервис кеширования query запросов
 */
class QueryCacheService
{
    protected string $driver = '';
    protected array $exclude = [];


    public function __construct()
    {
        $this->driver = config('laravel-helper.query_cache.driver') ?: config('cache.default');
        $this->exclude = config('laravel-helper.query_cache.exclude') ?? [];
    }


    /**
     * Возвращает сырой sql запрос из конструктора query запроса
     *
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return string
     */
    public function getSqlFromBuilder(EloquentBuilder|QueryBuilder|string $builder): string
    {
        return sql($builder);
    }


    /**
     * Возвращает массив названий таблиц из sql
     *
     * @param string $sql
     * @return array<string>
     */
    public function getTablesFromSql(string $sql): array
    {
        $tableCache = config('cache.stores.database.table', 'cache');

        return Helper::arrayDeleteValues(Helper::sqlTables($sql), [$tableCache]);
    }


    /**
     * Возвращает массив названий таблиц из массива моделей
     *
     * @param array<int, Model> $models
     * @return array
     */
    public function getTablesFromModels(array $models): array
    {
        $tables = [];

        foreach ($models as $model) {
            match (true) {
                $model instanceof Model => $tables[] = $model->getTable(),
                $model instanceof EloquentBuilder => $tables[] = $model->from,
                $model instanceof QueryBuilder => $tables = [
                    ...$tables,
                    ...$this->getTablesFromSql($this->getSqlFromBuilder($model)),
                    $model->from,
                ],

                default => $tables[] = $model,
            };
        }

        return $tables;
    }


    /**
     * Возвращает массив тегов для кеша
     *
     * @param mixed ...$tags
     * @return array
     */
    public function getQueryTags(mixed ...$tags): array
    {
        $result = [];

        foreach ($tags as $tag) {
            match (true) {
                $tag instanceof Model => $result[] = $tag->getTable(),
                $tag instanceof EloquentBuilder,
                $tag instanceof QueryBuilder => $result = [
                    ...$result,
                    ...$this->getTablesFromSql($this->getSqlFromBuilder($tag)),
                ],
                is_object($tag) => $result[] = $tag::class,

                default => $result[] = Helper::castToString($tag),
            };
        }

        return array_unique(array_filter([Helper::pathClassName($this::class), ...$result]));
    }


    /**
     * Возвращает имя ключа кеша
     *
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return string
     */
    public function getQueryKey(EloquentBuilder|QueryBuilder|string $builder, ?array $tags = null): string
    {
        $hash = Helper::hashXxh128($this->getSqlFromBuilder($builder));

        switch (true) {
            case $builder instanceof EloquentBuilder:
                /** @var Model $model */
                $model = $builder->getModel();
                $id = $model ? Helper::stringConcat('__', '', $model->{$model->getKeyName()}) : '';
                break;

            default:
                $id = '';
        }

        $tag = ($tags !== null) ? Helper::stringConcat('__', $tags) : '';

        return '__' . Helper::stringConcat('__', $tag, $hash, $id);
    }


    /**
     * Возвращает результат query запроса из кеша
     *
     * @param array $tags
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return mixed
     */
    public function hasQueryCache(array $tags, EloquentBuilder|QueryBuilder|string $builder): mixed
    {
        // Если есть в тегах таблица из исключения, то кеш не сохранялся
        if (Helper::arraySearchValues($tags, $this->exclude)) {
            return false;
        }

        return (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? Cache::driver($this->driver)->tags($tags)->has($this->getQueryKey($builder))
            : Cache::driver($this->driver)->has($this->getQueryKey($builder, $tags));
    }


    /**
     * Возвращает результат query запроса из кеша
     *
     * @param array $tags
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryCache(array $tags, EloquentBuilder|QueryBuilder|string $builder, mixed $default = null): mixed
    {
        return (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? Cache::driver($this->driver)->tags($tags)->get($this->getQueryKey($builder), $default)
            : Cache::driver($this->driver)->get($this->getQueryKey($builder, $tags), $default);
    }


    /**
     * Сохраняет результат query запроса в кеш
     *
     * @param array $tags
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @param mixed $value
     * @param int|bool|null|null $ttl - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return void
     */
    public function setQueryCache(
        array $tags,
        EloquentBuilder|QueryBuilder|string $builder,
        mixed $value,
        int|bool|null $ttl = null,
    ): void {
        // Если есть в тегах таблица из исключения, то кеш не сохраняем
        if (Helper::arraySearchValues($tags, $this->exclude)) {
            return;
        }

        $ttl = match (true) {
            is_integer($ttl) => $ttl,
            is_null($ttl), $ttl === true => config('laravel-helper.query_cache.ttl'),

            default => false,
        };

        ($ttl === false) ?: (
            (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? Cache::driver($this->driver)->tags($tags)->put($this->getQueryKey($builder), $value, $ttl)
            : Cache::driver($this->driver)->put($this->getQueryKey($builder, $tags), $value, $ttl)
        );
    }


    /**
     * Сбрасывает кеш
     *
     * @param Model $model
     * @param string|null $relation
     * @param Collection|null $pivotedModels
     * @return void
     */
    public function flush(Model|string $model, ?string $relation = null, ?Collection $pivotedModels = null): void
    {
        $tags = $this->getQueryTags($model, $relation, ...$pivotedModels?->all() ?? []);

        // Если есть в тегах таблица из исключения, то кеш не сохранялся
        if (Helper::arraySearchValues($tags, $this->exclude)) {
            return;
        }

        (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? Cache::driver($this->driver)->tags($tags)->flush()
            : $this->forgetCacheByPattern($tags);
    }


    /**
     * Удаляет кеш-ключи по маске для различных драйверов
     *
     * @param array $tags
     * @return void
     */
    public function forgetCacheByPattern(array $tags): void
    {
        $tag = '__' . Helper::stringConcat('__', $tags) . '__';

        switch ($driver = Cache::driver($this->driver)->getStore()::class) {

            //?!? redis
            // CACHE_STORE=redis
            case \Illuminate\Cache\RedisStore::class:
                $cursor = null;

                do {
                    [$cursor, $keys] = Redis::scan($cursor, [
                        'match' => "{$tag}",
                        'count' => 100,
                    ]);
                    foreach ($keys as $key) {
                        Redis::del($key);
                    }
                } while ($cursor != 0);
                break;

            //?!? file
            // CACHE_STORE=file
            case \Illuminate\Cache\FileStore::class:
                $path = storage_path('framework/cache/data');
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                );

                foreach ($files as $file) {
                    if (!Str::contains($file->getFilename(), '.')) {
                        $contents = @file_get_contents($file->getRealPath());
                        if ($contents && Str::contains($contents, $tag)) {
                            @unlink($file->getRealPath());
                        }
                    }
                }
                break;

            // CACHE_STORE=database
            case \Illuminate\Cache\DatabaseStore::class:
                $tableCache = config('cache.stores.database.table', 'cache');
                DB::table($tableCache)->where('key', 'like', "%{$tag}%")->delete();
                break;

            // CACHE_STORE=array
            case \Illuminate\Cache\ArrayStore::class:
                // ArrayStore не поддерживает хранение между запросами
                break;

            //?!? memcached
            // CACHE_STORE=memcached
            case \Illuminate\Cache\MemcachedStore::class:
                // Нет wildcard, нужно логировать ключи отдельно
                break;

            default:
            // Сброс кеша по шаблону не поддерживается для драйвера: {$driver}
        }
    }
}
