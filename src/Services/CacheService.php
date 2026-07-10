<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\HttpCacheConfigDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;
use Carbon\Carbon;
use FilesystemIterator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use Throwable;

/**
 * @internal
 * Сервис кеширования
 */
class CacheService extends DefaultService
{
    // Количество попыток записи кеша в файл
    public const CACHE_FILE_TRY_COUNT = 3;
    // Разделитель ключа кеша
    public const CACHE_TAGS_DELIMITER = '__';
    // Префикс gzip payload для database store, закодированного в ASCII-safe base64
    private const CACHE_DATABASE_GZ_BASE64_PREFIX = 'lh-gzbase64:v1:';


    /**
     * Возвращает время жизни кеша
     *
     * @param int|string|bool|null $ttl
     * @param ?HttpCacheConfigDto $config
     * @return bool|int|null
     */
    public function getCacheTtl(int|string|bool|null $ttl = null, ?HttpCacheConfigDto $config = null): bool|int|null
    {
        $now = now()->setTime(0, 0, 0, 0);
        $ttl ??= $config?->ttl;

        return match (true) {
            is_integer($ttl) => $ttl,
            $ttl === false, $ttl === 'false' => false,
            is_null($ttl), $ttl === true, $ttl === 'true' => (int)Lh::config(ConfigEnum::HttpCache, 'ttl'),
            is_numeric($ttl) => (int)$ttl,
            is_string($ttl) => (int)abs($now->copy()->modify(trim((string)$ttl, '+- '))->diffInSeconds($now)),

            default => false,
        };
    }


    /**
     * Проверяет наличие ключа в кеше
     *
     * @param ConfigEnum $config
     * @param array|null $tags
     * @param string $key
     * @return bool
     */
    public function hasCache(ConfigEnum $config, ?array $tags, string $key): bool
    {
        $driver = Lh::config($config, 'driver') ?: config('cache.default');
        in_array($config->value, $tags) ?: $tags = [$config->value, ...$tags];

        if (!$driver) {
            return false;

        } else if (Cache::driver($driver)->getStore() instanceof TaggableStore) {
            return Cache::driver($driver)->tags($tags)->has($key);

        } else {
            switch (Cache::driver($driver)->getStore()::class) {

                case RedisStore::class:

                    return Cache::driver($driver)->tags($tags)->has($key);

                case FileStore::class:
                    $path = rtrim(Lh::config($config, 'driver_file_path'), '/')
                        . '/' . Hlp::stringConcat(
                                static::CACHE_TAGS_DELIMITER,
                                Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                            );
                    $key = Hlp::stringSplitRange($key, static::CACHE_TAGS_DELIMITER, -2);
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
                        'ttl_default' => (int)Lh::config($config, 'ttl'),
                        'ttl_not_set' => null,

                        default => Hlp::castToInt(Hlp::stringSplit($ttlSplit, '_', -1)),
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
                    return Cache::driver($driver)->has($key);

                case ArrayStore::class:
                    $rootKey = Hlp::stringConcat(
                        static::CACHE_TAGS_DELIMITER,
                        Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                    );
                    $key = Hlp::stringSplit($key, static::CACHE_TAGS_DELIMITER, -1);
                    $cache = Hlp::cacheRuntimeGet(__CLASS__ . "_{$config->value}") ?? [];

                    if (!isset($cache[$rootKey][$key])) {
                        return false;
                    }

                    $data = $cache[$rootKey][$key] ?? [];
                    /** @var Carbon $createdAt */
                    $createdAt = $data['created_at'] ?? null;
                    $ttl = $data['ttl'] ?? null;
                    if (!is_null($ttl) && $createdAt->diffInSeconds() > $ttl) {
                        unset($cache[$key]);
                        Hlp::cacheRuntimeSet(__CLASS__ . "_{$config->value}", $cache);

                        return false;
                    }

                    return true;

                case MemcachedStore::class:
                    return false;
            }
        }

        return false;
    }


    /**
     * Сохраняет ключ в кеше
     *
     * @param ConfigEnum $config
     * @param array|null $tags
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function setCache(ConfigEnum $config, ?array $tags, string $key, mixed $value, int $ttl): bool
    {
        $driver = Lh::config($config, 'driver') ?: config('cache.default');
        $isDatabaseStore = $driver && Cache::driver($driver)->getStore()::class === DatabaseStore::class;
        $gzDeflate = (Lh::config($config, 'gzdeflate.enabled') ?? false)
            ? (Lh::config($config, 'gzdeflate.level') ?? -1)
            : false;
        $value = serialize($value);
        if ($gzDeflate !== false) {
            $value = gzdeflate($value, $gzDeflate);
            $value = $isDatabaseStore
                ? self::CACHE_DATABASE_GZ_BASE64_PREFIX . base64_encode($value)
                : $value;
        }
        in_array($config->value, $tags) ?: $tags = [$config->value, ...$tags];

        if (!$driver) {
            return false;

        } else if (Cache::driver($driver)->getStore() instanceof TaggableStore) {
            return Cache::driver($driver)->tags($tags)->put($key, $value, $ttl ?: null);

        } else {
            switch (Cache::driver($driver)->getStore()::class) {

                case RedisStore::class:
                    return Cache::driver($driver)->tags($tags)->put($key, $value, $ttl ?: null);

                case FileStore::class:
                    $path = rtrim(Lh::config($config, 'driver_file_path'), '/')
                        . '/' . Hlp::stringConcat(
                                static::CACHE_TAGS_DELIMITER,
                                Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                            );
                    $key = Hlp::stringSplitRange($key, static::CACHE_TAGS_DELIMITER, -2);
                    $file = "{$path}/$key.cache";

                    if (!$path || !File::exists($path)) {
                        File::makeDirectory($path)
                            ?: throw new LaravelHelperException("Ошибка создания папки кеша {$path}");
                    }

                    $try = 0;
                    while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                        if (File::put($file, $value, true)) {
                            return true;
                        }

                        usleep(10000);
                    }

                    return false;

                case DatabaseStore::class:
                    return Cache::driver($driver)->put($key, $value, $ttl ?: null);

                case ArrayStore::class:
                    $rootKey = Hlp::stringConcat(
                        static::CACHE_TAGS_DELIMITER,
                        Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                    );
                    $key = Hlp::stringSplit($key, static::CACHE_TAGS_DELIMITER, -1);
                    $cache = Hlp::cacheRuntimeGet(__CLASS__ . "_{$config->value}");

                    $cache[$rootKey][$key] = [
                        'value'      => $value,
                        'created_at' => now(),
                        'ttl'        => $ttl ?: null,
                    ];
                    Hlp::cacheRuntimeSet(__CLASS__ . "_{$config->value}", $cache);

                    return true;

                case MemcachedStore::class:
                    return false;
            }
        }

        return false;
    }


    /**
     * Возвращает ключ из кеша
     *
     * @param ConfigEnum $config
     * @param array|null $tags
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getCache(ConfigEnum $config, ?array $tags, string $key, mixed $default = null): mixed
    {
        $result = null;
        $driver = Lh::config($config, 'driver') ?: config('cache.default');
        $isDatabaseStore = $driver && Cache::driver($driver)->getStore()::class === DatabaseStore::class;
        in_array($config->value, $tags) ?: $tags = [$config->value, ...$tags];

        if (!$driver) {
            $result = null;

        } else if (Cache::driver($driver)->getStore() instanceof TaggableStore) {
            $result = Cache::driver($driver)->tags($tags)->get($key, $default);

        } else {
            switch (Cache::driver($driver)->getStore()::class) {

                case RedisStore::class:
                    $result = Cache::driver($driver)->tags($tags)->get($key, $default);
                    break;

                case FileStore::class:
                    $path = rtrim(Lh::config($config, 'driver_file_path'), '/')
                        . '/' . Hlp::stringConcat(
                                static::CACHE_TAGS_DELIMITER,
                                Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                            );
                    $key = Hlp::stringSplitRange($key, static::CACHE_TAGS_DELIMITER, -2);
                    $file = "{$path}/$key.cache";

                    if (!$path || !File::exists($path) || !File::isFile($file) || !File::exists($file)) {
                        $result = null;
                        break;
                    }

                    $try = 0;
                    while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                        try {
                            if ($result = File::get($file, true)) {
                                $try = static::CACHE_FILE_TRY_COUNT;
                            }

                        } catch (Throwable $exception) {
                        }

                        ($try >= static::CACHE_FILE_TRY_COUNT) ?: usleep(10000);
                    }

                    $result = $result ?: null;
                    break;

                case DatabaseStore::class:
                    $result = Cache::driver($driver)->get($key, $default);
                    break;

                case ArrayStore::class:
                    $rootKey = Hlp::stringConcat(
                        static::CACHE_TAGS_DELIMITER,
                        Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                    );
                    $key = Hlp::stringSplit($key, static::CACHE_TAGS_DELIMITER, -1);

                    $result = (Hlp::cacheRuntimeGet(__CLASS__ . "_{$config->value}") ?? [])[$rootKey][$key]['value']
                        ?? null;
                    break;

                case MemcachedStore::class:
                    $result = null;
                    break;
            }
        }

        $gzDeflate = (Lh::config($config, 'gzdeflate.enabled') ?? false)
            ? (Lh::config($config, 'gzdeflate.level') ?? -1)
            : false;

        // Без gzip cache payload должен быть обычной сериализованной строкой, а не бинарными данными.
        if (!is_null($result) && is_string($result) && !mb_check_encoding($result, 'UTF-8')) {
            if ($gzDeflate === false) {
                return null;
            }
        }

        if (
            $gzDeflate !== false
            && $isDatabaseStore
            && is_string($result)
            && str_starts_with($result, self::CACHE_DATABASE_GZ_BASE64_PREFIX)
        ) {
            $decoded = base64_decode(
                substr($result, strlen(self::CACHE_DATABASE_GZ_BASE64_PREFIX)),
                true,
            );

            if ($decoded === false) {
                return null;
            }

            $result = $decoded;
        }

        $result = is_null($result)
            ? null
            : (
                ($gzDeflate !== false)
                ? ((($tmp = @unserialize(@gzinflate($result))) === false) ? null : $tmp)
                : @unserialize($result)
            );
        return $result;
    }


    /**
     * Удаляет ключи из кеша по маске для различных драйверов
     *
     * @param ConfigEnum $config
     * @param array $tags
     * @return void
     */
    public function clearCache(ConfigEnum $config, array $tags): void
    {
        if (!Lh::config($config, 'enabled')) {
            return;
        }

        $driver = Lh::config($config, 'driver') ?: config('cache.default');
        $tags = Hlp::arrayDeleteValues($tags, [Hlp::pathClassName($this::class), 'ttl_*']);

        if (!$driver) {
            return;

        } else if (Cache::driver($driver)->getStore() instanceof TaggableStore) {
            Cache::driver($driver)->tags($tags)->flush();

        } else {
            switch (Cache::driver($driver)->getStore()::class) {

                // CACHE_STORE=redis
                case RedisStore::class:
                    Cache::driver($driver)->tags($tags)->flush();
                    break;

                // CACHE_STORE=file
                case FileStore::class:
                    $path = rtrim(Lh::config($config, 'driver_file_path'), '/');

                    if (!$path || !File::exists($path)) {
                        return;
                    }

                    $isFullFlush = in_array('*', $tags);
                    $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

                    foreach ($iterator as $fileinfo) {
                        /** @var SplFileInfo $fileinfo */
                        if ($fileinfo->isDir()) {
                            if (
                                $isFullFlush
                                || Hlp::stringSplitSearch($fileinfo->getFilename(), static::CACHE_TAGS_DELIMITER, $tags)
                            ) {
                                $try = 0;
                                while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                                    File::deleteDirectory($fileinfo->getRealPath())
                                        ? $try = static::CACHE_FILE_TRY_COUNT
                                        : usleep(10000);
                                }
                            }
                        }
                    }

                    // $files = new RecursiveIteratorIterator(
                    //     new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    // );

                    // foreach ($files as $file) {
                    //     /** @var SplFileInfo $file */
                    //     $pathFile = $file->getRealPath();
                    //     if (
                    //         $isFullFlush
                    //         || Hlp::stringSplitSearch($file->getFilename(), static::CACHE_TAGS_DELIMITER, $tags)
                    //     ) {
                    //         $try = 0;
                    //         while (++$try <= static::CACHE_FILE_TRY_COUNT) {
                    //             File::delete($pathFile)
                    //                 ? $try = static::CACHE_FILE_TRY_COUNT
                    //                 : usleep(10000);
                    //         }
                    //     }
                    // }
                    break;

                // CACHE_STORE=database
                case DatabaseStore::class:
                    $tableCache = config('cache.stores.database.table', 'cache');
                    $connection = DB::connection(config('cache.stores.database.connection'));

                    if (!$connection->getSchemaBuilder()->hasTable($tableCache)) {
                        break;
                    }

                    $tags = $this->getDatabaseStoreClearTags($config, $tags);

                    if (in_array('*', $tags, true)) {
                        $connection->table($tableCache)->delete();
                        break;
                    }

                    if ($tags === []) {
                        break;
                    }

                    $connection->table($tableCache)
                        ->where(static function ($query) use ($tags) {
                            foreach ($tags as $tag) {
                                $query->orWhere(
                                    'key',
                                    'like',
                                    '%' . static::CACHE_TAGS_DELIMITER . $tag . static::CACHE_TAGS_DELIMITER . '%',
                                );
                            }
                        })
                        ->delete();
                    break;

                // CACHE_STORE=array
                case ArrayStore::class:
                    if (in_array($tags, ['*'])) {
                        Hlp::cacheRuntimeClear();

                    } else {
                        $rootKey = Hlp::stringConcat(
                            static::CACHE_TAGS_DELIMITER,
                            Hlp::arrayDeleteValues($tags, ['ttl_*', 'hash_*']),
                        );
                        $cache = Hlp::cacheRuntimeGet(__CLASS__ . "_{$config->value}") ?? [];
                        foreach (array_keys($cache) as $rootKey) {
                            if (Hlp::stringSplitSearch($rootKey, static::CACHE_TAGS_DELIMITER, $tags)) {
                                unset($cache[$rootKey]);
                            }
                        }

                        Hlp::cacheRuntimeSet(__CLASS__ . "_{$config->value}", $cache);
                    }
                    break;

                // CACHE_STORE=memcached
                case MemcachedStore::class:
                    break;
            }
        }
    }


    /**
     * Возвращает теги для поиска ключей database store.
     *
     * DatabaseStore не поддерживает Laravel tags, поэтому query cache кодирует теги в key.
     * Служебный root tag используется только для полной очистки конкретного cache config.
     *
     * @param ConfigEnum $config
     * @param array $tags
     * @return array<int, string>
     */
    private function getDatabaseStoreClearTags(ConfigEnum $config, array $tags): array
    {
        $isFullFlush = in_array('*', $tags, true);
        $isConfigFlush = in_array($config->value, $tags, true);
        $rootTag = $this->getDatabaseStoreRootTag($config);

        if ($isFullFlush) {
            return ['*'];
        }

        if ($isConfigFlush) {
            return $rootTag ? [$rootTag] : [];
        }

        $tags = Hlp::arrayDeleteValues(
            $tags,
            array_values(array_filter([
                Hlp::pathClassName($this::class),
                $rootTag,
                'ttl_*',
                'hash_*',
            ])),
        );

        return collect($tags)
            ->map(static fn ($tag) => Hlp::castToString($tag))
            ->filter(static fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();
    }


    /**
     * Возвращает root tag, который реально присутствует в database cache key.
     *
     * @param ConfigEnum $config
     * @return string|null
     */
    private function getDatabaseStoreRootTag(ConfigEnum $config): ?string
    {
        return match ($config) {
            ConfigEnum::QueryCache => Hlp::pathClassName(QueryCacheService::class),

            default => null,
        };
    }
}
