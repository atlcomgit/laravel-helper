<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Carbon\Carbon;
use FilesystemIterator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Сервис кеширования query запросов
 */
class QueryCacheService
{
    // Количество попыток записи кеша в файл
    public const CACHE_FILE_TRY_COUNT = 3;
    public const CACHE_TAGS_DELIMITER = '__';

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
     * @param string|null $sql
     * @return array<string>
     */
    public function getTablesFromSql(?string $sql): array
    {
        $tableCache = config('cache.stores.database.table', 'cache');

        return Hlp::arrayDeleteValues(Hlp::sqlTables($sql ?? ''), [$tableCache]);
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

        return array_unique(array_filter($tables));
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

                default => $result[] = Hlp::castToString($tag),
            };
        }

        return array_unique(array_filter([Hlp::pathClassName($this::class), ...$result]));
    }


    /**
     * Возвращает имя ключа кеша
     *
     * @param array|null $tags
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return string|null
     */
    public function getQueryKey(?array $tags = null, EloquentBuilder|QueryBuilder|string $builder): ?string
    {
        // Если есть в тегах таблица из исключения, то кеш не используется
        if (Hlp::arraySearchValues($tags, $this->exclude)) {
            return null;
        }

        $sql = $this->getSqlFromBuilder($builder);
        $hash = Hlp::hashXxh128($sql);

        switch (true) {
            case $builder instanceof EloquentBuilder:
                /** @var Model $model */
                $model = $builder->getModel();
                $id = $model ? Hlp::stringConcat(static::CACHE_TAGS_DELIMITER, '', $model->{$model->getKeyName()}) : '';
                break;

            default:
                $id = '';
        }

        $tag = (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? ''
            : Hlp::stringConcat(static::CACHE_TAGS_DELIMITER, $tags);

        return static::CACHE_TAGS_DELIMITER . Hlp::stringConcat(static::CACHE_TAGS_DELIMITER, $tag, $hash, $id);
    }


    /**
     * Возвращает результат query запроса из кеша
     *
     * @param array|null $tags
     * @param string|null $key
     * @return bool
     */
    public function hasQueryCache(?array $tags = null, ?string $key): bool
    {
        if (!$key) {
            return false;
        }

        return $this->hasCache($tags, $key);
    }


    /**
     * Сохраняет результат query запроса в кеш по тегам и ключу
     *
     * @param array|null $tags
     * @param string|null $key
     * @param mixed $value
     * @param int|bool|null $ttl - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return bool
     */
    public function setQueryCache(?array $tags = null, ?string $key, mixed $value, int|bool|null $ttl = null): bool
    {
        if (!$key) {
            return false;
        }

        $ttl = match (true) {
            is_integer($ttl) => $ttl,
            is_null($ttl), $ttl === true => (int)config('laravel-helper.query_cache.ttl'),

            default => false,
        };

        return ($ttl !== false)
            ? $this->setCache($tags, $key, $value, $ttl)
            : false;
    }


    /**
     * Возвращает результат query запроса из кеша по тегам и ключу
     *
     * @param array|null $tags
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryCache(?array $tags = null, ?string $key, mixed $default = null): mixed
    {
        if (!$key) {
            return null;
        }

        return $this->getCache($tags, $key, $default);
    }


    /**
     * Сбрасывает кеш по тегам
     *
     * @param Model $model
     * @param string|null $relation
     * @param Collection|null $pivotedModels
     * @return void
     */
    public function flushQueryCache(Model|string $table, ?string $relation = null, ?Collection $pivotedModels = null): void
    {
        $tags = $this->getQueryTags($table, $relation, ...$pivotedModels?->all() ?? []);

        // Если таблица не в игноре и теги не в исключении, то чистим кеш (иначе кеш не сохранялся)
        if (
            app(LaravelHelperService::class)->notFoundIgnoreTables($tags)
            && !Hlp::arraySearchValues($tags, $this->exclude)
        ) {
            (Cache::driver($this->driver)->getStore() instanceof TaggableStore)
                ? Cache::driver($this->driver)->tags($tags)->flush()
                : $this->flushCache($tags);
        }
    }


    /**
     * Проверяет наличие query запроса в кеше
     *
     * @param array|null $tags
     * @param string|null $key
     * @return bool
     */
    private function hasCache(?array $tags, ?string $key): bool
    {
        if (Cache::driver($this->driver)->getStore() instanceof TaggableStore) {
            return Cache::driver($this->driver)->tags($tags)->has($key);

        } else {
            switch (Cache::driver($this->driver)->getStore()::class) {
                case RedisStore::class:
                    return false;

                case FileStore::class:
                    $path = rtrim(config('laravel-helper.query_cache.driver_file_path'), '/');
                    $file = "{$path}/$key.cache";

                    if (!$path || !File::exists($file)) {
                        return false;
                    }

                    $ttlMask = '*ttl_*';
                    $ttls = Hlp::stringSplitSearch($key, static::CACHE_TAGS_DELIMITER, $ttlMask);
                    if (!$ttls) {
                        return false;
                    }

                    $ttl = match ($ttlSplit = Hlp::stringSplit($key, static::CACHE_TAGS_DELIMITER, $ttls[$ttlMask][0] ?? 0)) {
                        'ttl_default' => (int)config('laravel-helper.query_cache.ttl'),
                        'ttl_not_set' => null,

                        default => Hlp::castToInt($ttlSplit),
                    };
                    $createdAt = Carbon::createFromTimestamp(File::lastModified($path));

                    if (!is_null($ttl) && $createdAt->diffInSeconds() > $ttl) {
                        $try = 0;
                        while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                            if (File::delete($file)) {
                                break;
                            }

                            usleep(10000);
                        }

                        return false;
                    }

                    return true;

                case DatabaseStore::class:
                    return Cache::driver($this->driver)->has($key);

                case ArrayStore::class:
                    return false;

                case MemcachedStore::class:
                    return false;
            }
        }

        return false;
    }


    /**
     * Сохраняет query запрос в кеш
     *
     * @param array|null $tags
     * @param string|null $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    private function setCache(?array $tags, ?string $key, mixed $value, int $ttl): bool
    {
        if (Cache::driver($this->driver)->getStore() instanceof TaggableStore) {
            return Cache::driver($this->driver)->tags($tags)->put($key, $value, $ttl);

        } else {
            switch (Cache::driver($this->driver)->getStore()::class) {
                case RedisStore::class:
                    return false;

                case FileStore::class:
                    $path = rtrim(config('laravel-helper.query_cache.driver_file_path'), '/');
                    $file = "{$path}/$key.cache";

                    if (!$path || !File::exists($path)) {
                        File::makeDirectory($path)
                            ?: throw new LaravelHelperException("Ошибка создания папки кеша {$path}");
                    }

                    $try = 0;
                    $data = serialize($value);
                    while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                        if (File::put($file, $data, true)) {
                            return true;
                        }

                        usleep(10000);
                    }

                    return false;

                case DatabaseStore::class:
                    return Cache::driver($this->driver)->put($key, $value, $ttl);

                case ArrayStore::class:
                    return false;

                case MemcachedStore::class:
                    return false;
            }
        }

        return false;
    }


    /**
     * Сохраняет query запрос в кеш
     *
     * @param array|null $tags
     * @param string|null $key
     * @param mixed $value
     * @param mixed|null $default
     * @return mixed
     */
    private function getCache(?array $tags, ?string $key, mixed $value, mixed $default = null): mixed
    {
        if (Cache::driver($this->driver)->getStore() instanceof TaggableStore) {
            return Cache::driver($this->driver)->tags($tags)->get($key, $default);

        } else {
            switch (Cache::driver($this->driver)->getStore()::class) {
                case RedisStore::class:
                    return null;

                case FileStore::class:
                    $path = rtrim(config('laravel-helper.query_cache.driver_file_path'), '/');
                    $file = "{$path}/$key.cache";

                    if (!$path || !File::exists($path) || !File::isFile($file) || !File::exists($file)) {
                        return null;
                    }

                    $try = 0;
                    while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                        try {
                            if ($data = File::get($file, true)) {
                                return unserialize($data);
                            }

                        } catch (Throwable $exception) {
                        }

                        usleep(10000);
                    }

                    return null;

                case DatabaseStore::class:
                    return Cache::driver($this->driver)->get($key, $default);

                case ArrayStore::class:
                    return null;

                case MemcachedStore::class:

                    return null;
            }
        }

        return null;
    }


    /**
     * Удаляет кеш-ключи по маске для различных драйверов
     *
     * @param array $tags
     * @return void
     */
    private function flushCache(array $tags): void
    {
        $tag = '__' . Hlp::stringConcat('__', $tags) . '__';

        if (Cache::driver($this->driver)->getStore() instanceof TaggableStore) {
            Cache::driver($this->driver)->tags($tags)->flush();

        } else {
            switch (Cache::driver($this->driver)->getStore()::class) {

                //?!? проверить redis
                // CACHE_STORE=redis
                case RedisStore::class:
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

                //?!? проверить file
                // CACHE_STORE=file
                case FileStore::class:
                    $path = rtrim(config('laravel-helper.query_cache.driver_file_path'), '/');

                    if (!$path || !File::exists($path)) {
                        return;
                    }

                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    );

                    foreach ($files as $file) {
                        /** @var SplFileInfo $file */
                        $pathFile = $file->getRealPath();
                        if (Hlp::stringSplitSearch($file->getFilename(), static::CACHE_TAGS_DELIMITER, $tags)) {
                            $try = 0;
                            while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                                File::delete($pathFile)
                                    ? $try = static::CACHE_FILE_TRY_COUNT
                                    : usleep(10000);
                            }
                        }
                    }
                    break;

                // CACHE_STORE=database
                case DatabaseStore::class:
                    $tableCache = config('cache.stores.database.table', 'cache');
                    DB::table($tableCache)->where('key', 'like', "%{$tag}%")->delete();
                    break;

                // CACHE_STORE=array
                case ArrayStore::class:
                    // ArrayStore не поддерживает хранение между запросами
                    break;

                //?!? проверить memcached
                // CACHE_STORE=memcached
                case MemcachedStore::class:
                    // Нет wildcard, нужно логировать ключи отдельно
                    break;

                default:
                // Сброс кеша по шаблону не поддерживается для драйвера
            }
        }
    }
}
