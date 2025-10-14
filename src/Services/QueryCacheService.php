<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\QueryCacheEventDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;
use Atlcom\LaravelHelper\Events\QueryCacheEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @internal
 * Сервис кеширования query запросов
 */
class QueryCacheService extends DefaultService
{
    protected CacheService $cacheService;
    protected string $driver = '';
    protected array $exclude = [];


    public function __construct()
    {
        $this->cacheService = app(CacheService::class);
        $this->driver = Lh::config(ConfigEnum::QueryCache, 'driver') ?: config('cache.default');
        $this->exclude = Lh::config(ConfigEnum::QueryCache, 'exclude') ?? [];
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

        foreach ($tables as $key => $table) {
            !($table instanceof Expression) ?: $tables[$key] = $table->getValue(new Grammar());
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
    public function getQueryKey(?array $tags = null, EloquentBuilder|QueryBuilder|string $builder = ''): ?string
    {
        // Если есть в тегах таблица из исключения, то кеш не используется
        if (Hlp::arraySearchValues($tags, $this->exclude)) {
            return null;
        }

        $sql = $this->getSqlFromBuilder($builder);
        $hash = 'hash_' . Hlp::hashXxh128(gettype($builder) . $sql);

        switch (true) {
            // case $builder instanceof EloquentBuilder:
            //     /** @var Model $model */
            //     $model = $builder->getModel();
            //     $id = $model
            //         ? '_' . Hlp::stringConcat(CacheService::CACHE_TAGS_DELIMITER, '', $model->{$model->getKeyName()})
            //         : '';
            //     break;

            default:
                $id = '';
        }

        $tag = ($this->driver && Cache::driver($this->driver)->getStore() instanceof TaggableStore)
            ? ''
            : Hlp::stringConcat(CacheService::CACHE_TAGS_DELIMITER, $tags);

        return CacheService::CACHE_TAGS_DELIMITER
            . Hlp::stringConcat(CacheService::CACHE_TAGS_DELIMITER, $tag, "{$hash}{$id}");
    }


    /**
     * Возвращает результат query запроса из кеша
     *
     * @param array|null $tags
     * @param string|null $key
     * @return bool
     */
    public function hasQueryCache(?array $tags = null, ?string $key = null): bool
    {
        if (!$key) {
            return false;
        }

        return $this->cacheService->hasCache(ConfigEnum::QueryCache, $tags, $key);
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
    public function setQueryCache(?array $tags = null, ?string $key = null, mixed $value = null, int|bool|null $ttl = null): bool
    {
        return $this->withoutTelescope(
            function () use (&$tags, &$key, &$value, &$ttl) {
                if (!$key) {
                    return false;
                }

                $ttl = match (true) {
                    is_integer($ttl) => $ttl,
                    is_null($ttl), $ttl === true => (int)Lh::config(ConfigEnum::QueryCache, 'ttl'),

                    default => false,
                };

                $result = ($ttl !== false)
                    ? $this->cacheService->setCache(ConfigEnum::QueryCache, $tags, $key, $value, $ttl)
                    : false;

                event(
                    new QueryCacheEvent(
                        QueryCacheEventDto::create(
                            type: EventTypeEnum::SetQueryCache,
                            tags: $tags,
                            key: $key,
                            ttl: $ttl,
                            data: $value,
                        ),
                    ),
                );

                return $result;
            }
        );
    }


    /**
     * Возвращает результат query запроса из кеша по тегам и ключу
     *
     * @param array|null $tags
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryCache(?array $tags = null, ?string $key = null, mixed $default = null): mixed
    {
        return $this->withoutTelescope(
            function () use (&$tags, &$key, &$default) {
                if (!$key) {
                    return null;
                }

                $result = $this->cacheService->getCache(ConfigEnum::QueryCache, $tags, $key, $default);

                event(
                    new QueryCacheEvent(
                        QueryCacheEventDto::create(
                            type: EventTypeEnum::GetQueryCache,
                            tags: $tags,
                            key: $key,
                            data: $result,
                        ),
                    ),
                );

                return $result;
            }
        );
    }


    /**
     * Сбрасывает кеш query запросов по тегам
     *
     * @param Model $model
     * @param string|null $relation
     * @param Collection<Model>|null $pivotedModels
     * @return void
     */
    public function clearQueryCache(Model|string $table, ?string $relation = null, ?Collection $pivotedModels = null): void
    {
        $this->withoutTelescope(
            function () use (&$table, &$relation, &$pivotedModels) {
                $tags = $this->getQueryTags($table, $relation, [$pivotedModels?->first()?->getTable()]);

                // Если таблица не в игноре и теги не в исключении, то чистим кеш (иначе кеш не сохранялся)
                if (
                    Lh::notFoundIgnoreTables($tags)
                    && !Hlp::arraySearchValues($tags, $this->exclude)
                ) {
                    $this->cacheService->clearCache(ConfigEnum::QueryCache, $tags);

                    event(
                        new QueryCacheEvent(
                            QueryCacheEventDto::create(
                                type: EventTypeEnum::ClearQueryCache,
                                tags: $tags,
                            ),
                        ),
                    );
                }
            }
        );
    }


    /**
     * Сбрасывает весь кеш query запросов
     *
     * @return void
     */
    public function clearQueryCacheAll(): void
    {
        $this->withoutTelescope(
            function () {
                $this->cacheService->clearCache(ConfigEnum::QueryCache, $tags = [ConfigEnum::QueryCache->value]);

                event(
                    new QueryCacheEvent(
                        QueryCacheEventDto::create(
                            type: EventTypeEnum::ClearQueryCache,
                            tags: $tags,
                        ),
                    ),
                );
            }
        );
    }
}
