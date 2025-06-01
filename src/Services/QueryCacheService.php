<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Exception;
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
    /**
     * Возвращает сырой sql запрос из конструктора query запроса
     *
     * @param EloquentBuilder|QueryBuilder $builder
     * @return string
     */
    public function getSqlFromBuilder(EloquentBuilder|QueryBuilder $builder): string
    {
        return sql($builder);
    }


    /**
     * Возвращает название таблицы из 
     *
     * @param string $sql
     * @return array<string>
     */
    public function getTablesFromSql(string $sql): array
    {
        $tables = [];

        // Удаляем лишние пробелы и нормализуем SQL
        $normalizedSql = preg_replace('/\s+/', ' ', $sql);

        // Ищем после ключевых слов FROM, JOIN, UPDATE, INTO, DELETE FROM, TRUNCATE (TABLE — необязателен)
        $pattern = '/\b(FROM|JOIN|UPDATE|INTO|DELETE FROM|TRUNCATE(?: TABLE)?)\s+((?:[`"\[]?[a-zA-Z0-9_.]+[`"\]]?))/i';

        if (preg_match_all($pattern, $normalizedSql, $matches)) {
            foreach ($matches[2] as $rawTable) {
                // Удаляем кавычки и квадратные скобки вокруг имени таблицы
                $table = preg_replace('/^[`\["]?|[`"\]]?$/', '', $rawTable);

                $tables[] = $table;
            }
        }

        return array_values(array_unique($tables));
    }


    /**
     * Возвращает массив тегов для кеша
     *
     * @param mixed ...$tags
     * @return array
     */
    public function getQueryTags(mixed ...$tags): array
    {
        $result = [Helper::pathClassName($this::class)];

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

        return array_unique($result);
    }


    /**
     * Возвращает имя ключа кеша
     *
     * @param EloquentBuilder|QueryBuilder $builder
     * @return string
     */
    public function getQueryKey(EloquentBuilder|QueryBuilder $builder, ?array $tags = null): string
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
     * @param EloquentBuilder|QueryBuilder $builder
     * @return mixed
     */
    public function hasQueryCache(array $tags, EloquentBuilder|QueryBuilder $builder): mixed
    {
        return (Cache::getStore() instanceof TaggableStore)
            ? Cache::tags($tags)->has($this->getQueryKey($builder))
            : Cache::has($this->getQueryKey($builder, $tags));
    }


    /**
     * Возвращает результат query запроса из кеша
     *
     * @param array $tags
     * @param EloquentBuilder|QueryBuilder $builder
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryCache(array $tags, EloquentBuilder|QueryBuilder $builder, mixed $default = null): mixed
    {
        return (Cache::getStore() instanceof TaggableStore)
            ? Cache::tags($tags)->get($this->getQueryKey($builder), $default)
            : Cache::get($this->getQueryKey($builder, $tags), $default);
    }


    /**
     * Сохраняет результат query запроса в кеш
     *
     * @param array $tags
     * @param EloquentBuilder|QueryBuilder $builder
     * @param mixed $value
     * @param int|bool|null|null $ttl - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return void
     */
    public function setQueryCache(
        array $tags,
        EloquentBuilder|QueryBuilder $builder,
        mixed $value,
        int|bool|null $ttl = null,
    ): void {
        $ttl = match (true) {
            is_integer($ttl) => $ttl,
            is_null($ttl), $ttl === true => config('laravel-helper.query_cache.ttl'),

            default => false,
        };

        ($ttl === false) ?: (
            (Cache::getStore() instanceof TaggableStore)
            ? Cache::tags($tags)->put($this->getQueryKey($builder), $value, $ttl)
            : Cache::put($this->getQueryKey($builder, $tags), $value, $ttl)
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
    //?!? 
    public function flush(Model $model, ?string $relation = null, ?Collection $pivotedModels = null): void
    {
        $tags = $this->getQueryTags($model);

        (Cache::getStore() instanceof TaggableStore)
            ? Cache::tags($tags)->flush()
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

        switch ($driver = Cache::getStore()::class) {
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

            case \Illuminate\Cache\DatabaseStore::class:
                // проверить кеш в файлах
                $table = config('cache.stores.database.table', 'cache');
                DB::table($table)->where('key', 'like', "%$tag%")->delete();
                break;

            case \Illuminate\Cache\ArrayStore::class:
                // ArrayStore не поддерживает хранение между запросами
                break;

            case \Illuminate\Cache\MemcachedStore::class:
                // Нет wildcard, нужно логировать ключи отдельно
                break;

            default:
                // Сброс кеша по шаблону не поддерживается для драйвера: {$driver}
        }
    }
}
