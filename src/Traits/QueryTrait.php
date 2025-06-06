<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use stdClass;
use Throwable;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @method static|EloquentBuilder|QueryBuilder withCache(int|bool|null $seconds = null)
 * @method static|EloquentBuilder|QueryBuilder withLog(?bool $enabled = null)
 * @mixin \Illuminate\Database\Eloquent\Builder, \Illuminate\Database\Query\Builder
 */
trait QueryTrait
{
    /** Флаг включения кеширования запроса или ttl */
    protected int|bool|null $useWithCache = false;
    /** Флаг включения лога query запроса */
    protected bool|null $useWithLog = false;


    /**
     * Вызывает макрос включения кеша
     *
     * @param int|bool|null $seconds
     * @return static
     */
    public function withCache(int|bool|null $seconds = null): static
    {
        $this->setUseWithCache($seconds);

        return $this;
    }


    /**
     * Вызывает макрос включения лога
     *
     * @param bool|null $enabled
     * @return static
     */
    public function withLog(bool|null $enabled = null): static
    {
        $this->setUseWithLog($enabled);

        return $this;
    }


    /**
     * Устанавливает флаг включения кеширования
     *
     * @param int|bool|null $seconds - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function setUseWithCache(int|bool|null $seconds = null): static
    {
        $this->useWithCache = $seconds ?? true;

        return $this;
    }


    /**
     * Возвращает флаг включения кеширования
     *
     * @return int|bool|null
     */
    public function getUseWithCache(): int|bool|null
    {
        return $this->useWithCache;
    }


    /**
     * Устанавливает флаг включения лога query запроса
     *
     * @param bool|null $enabled
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function setUseWithLog(bool $enabled = null): static
    {
        $this->useWithLog = $enabled ?? true;

        return $this;
    }


    /**
     * Возвращает флаг включения кеширования
     *
     * @return bool
     */
    public function getUseWithLog(): bool
    {
        return $this->useWithLog;
    }


    /**
     * Возвращает название тега из ttl (дополнительно добавляется в ключ кеша)
     *
     * @param int|bool|null $ttl
     * @return string
     */
    protected function getTagTtl(int|bool|null $ttl): string
    {
        $ttl ??= $this->getUseWithCache();

        return match (true) {
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",
            is_null($ttl) => 'ttl_not_set',

            default => '',
        };

    }


    /**
     * @override
     * Выполняет запрос как оператор «select» с использованием кеша
     * @see parent::get()
     *
     * @param  array|string  $columns
     * @return Collection<int, TModel>
     */
    public function queryGet($columns = ['*']): Collection
    {
        try {
            $status = false;
            $withCache = $this->getUseWithCache();
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromModels(
                $this instanceof EloquentBuilder ? $this->getModels() : [$this]
            );
            $tableCache = config('cache.stores.database.table', 'cache');
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($this);

            if ($tables && !in_array($tableCache, $tables) && ($withCache === true || is_integer($withCache))) {
                $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($withCache)]);
                $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $this);
                $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                $result = $hasCache
                    ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                    : parent::get($columns);

                $hasCache ?: $queryCacheService->setQueryCache(tags: $tags, key: $cacheKey, value: $result, ttl: $withCache);
                $isCached = !$hasCache;
                $isFromCache = $hasCache;

                !($result instanceof Collection)
                    ?: $result->map(
                        fn (/** @var \Atlcom\LaravelHelper\Defaults\DefaultModel $item */ $item) => match (true) {
                            (($item instanceof Model) && method_exists($item, 'setFromCached'))
                            => $item->setCached($isCached)->setFromCached($isFromCache),

                            default => $item,
                        }
                    );


            } else {
                $result = parent::get($columns);
            }

            $status = true;

        } catch (Throwable $exception) {
            throw $exception;
        }

        $this->updateQueryLog($arrayQueryLogDto, $result, $cacheKey, $isCached, $isFromCache, $status);

        return $result;
    }


    /**
     * @override
     * Выполняет запрос как оператор «select» с использованием кеша
     * @see parent::select()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return Collection<int, stdClass>|array<int, stdClass>
     */
    public function querySelect($query, $bindings = [], $useReadPdo = true): Collection|array
    {
        try {
            $status = false;
            $withCache = $this->getUseWithCache();
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromSql($query);
            $tableCache = config('cache.stores.database.table', 'cache');
            $sql = sql($query, $bindings);
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($sql);

            if ($tables && !in_array($tableCache, $tables) && ($withCache === true || is_integer($withCache))) {
                $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($withCache)]);
                $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $sql);
                $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                $result = $hasCache
                    ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                    : parent::select($query, $bindings, $useReadPdo);

                $hasCache ?: $queryCacheService->setQueryCache(tags: $tags, key: $cacheKey, value: $result, ttl: $withCache);
                $isCached = !$hasCache;
                $isFromCache = $hasCache;

                !($result instanceof Collection)
                    ?: $result->map(
                        fn (/** @var \Atlcom\LaravelHelper\Defaults\DefaultModel $item */ $item) => match (true) {
                            (($item instanceof Model) && method_exists($item, 'setFromCached'))
                            => $item->setCached($isCached)->setFromCached($isFromCache),

                            default => $item,
                        }
                    );
            } else {
                $result = parent::select($query, $bindings, $useReadPdo);
            }

            $status = true;

        } catch (Throwable $exception) {
            throw $exception;
        }

        $this->updateQueryLog($arrayQueryLogDto, $result, $cacheKey, $isCached, $isFromCache, $status);

        return $result;
    }


    /**
     * Сбрасывает кеш моделей из конструктора
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return void
     */
    public function flushCache($query = null, $bindings = []): void
    {
        $queryCacheService = app(QueryCacheService::class);
        $models = $this instanceof EloquentBuilder
            ? $this->getModels()
            : $queryCacheService->getTablesFromSql($query);

        foreach ($models as $model) {
            $queryCacheService->flush($model);
        }
    }


    /**
     * [Description for createQueryLog]
     *
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return array<QueryLogDto>
     */
    protected function createQueryLog(EloquentBuilder|QueryBuilder|string $builder): array
    {
        $result = [];

        if (!config('laravel-helper.query_log.enabled') || !$this->getUseWithLog()) {
            return $result;
        }

        $sql = app(QueryCacheService::class)->getSqlFromBuilder($builder);
        $models = $this instanceof EloquentBuilder ? $this->getModels() : [$this];
        $exclude = config('laravel-helper.query_log.exclude') ?? [];

        foreach ($models as $model) {
            /** @var Model|QueryBuilder $model */
            $tables = $model instanceof Model ? [$model->getTable()] : Helper::sqlTables($sql);
            if (in_array($model::class, $exclude) || Helper::arraySearchValues($tables, $exclude)) {
                continue;
            }

            $dto = QueryLogDto::create(
                modelType: $model::class,
                modelId: $model instanceof Model ? $model->{$model->getKeyName()} : null,
                query: $sql,
                info: [
                    'fields' => Helper::sqlFields($sql),
                    'size_query' => Helper::stringLength($sql),
                ],
            );

            !config('laravel-helper.query_log.store_on_start') ?: $dto->dispatch();

            $result[] = $dto;
        }

        return $result;
    }


    /**
     * [Description for updateLog]
     *
     * @param array<QueryLogDto> $dto
     * @param mixed $result
     * @param string|null $cacheKey
     * @param bool|null $isCached
     * @param bool|null $isFromCache
     * @return void
     */
    protected function updateQueryLog(
        array $arrayQueryLogDto,
        mixed &$result,
        ?string $cacheKey = null,
        ?bool $isCached = null,
        ?bool $isFromCache = null,
        bool $status = false,
    ): void {
        foreach ($arrayQueryLogDto as $dto) {
            /** @var QueryLogDto $dto */
            $dto->cacheKey = $cacheKey;
            $dto->isCached = is_null($isCached) ? false : $isCached;
            $dto->isFromCache = is_null($isFromCache) ? false : $isFromCache;
            $dto->status = $status ? QueryLogStatusEnum::Success : QueryLogStatusEnum::Failed;
            $dto->isUpdated = true;
            $dto->info = [
                ...$dto->info,
                'duration' => $dto->getDuration(),
                'memory' => $dto->getMemory(),
                'size_result' => Helper::stringLength(json_encode($result, Helper::jsonFlags())),
                'count' => match (true) {
                    $result instanceof Collection => $result->count(),
                    is_array($result) => count($result),

                    default => $result,
                },
            ];

            $dto->dispatch();
        }
    }
}
