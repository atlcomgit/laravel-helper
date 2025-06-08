<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
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
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Connection
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
    public function setUseWithLog(?bool $enabled = null): static
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
     * Возвращает массив игнорируемых таблиц для кеша и лога
     *
     * @return array
     */
    public function getIgnoreTables(): array
    {
        static $result = null;

        return $result ??= [
            config('laravel-helper.console_log.table'),
            config('laravel-helper.http_log.table'),
            config('laravel-helper.model_log.table'),
            config('laravel-helper.queue_log.table'),
            config('laravel-helper.query_log.table'),
            config('laravel-helper.view_log.table'),
        ];
    }


    /**
     * Сохраняет лог перед query запросом
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
            if (
                in_array($model::class, $exclude)
                || app(LaravelHelperService::class)->checkIgnoreTables($tables)
                || Helper::arraySearchValues($tables, $exclude)
            ) {
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
     * Сохраняет лог после query запроса
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
            $dto->isUpdated = config('laravel-helper.query_log.store_on_start') ;
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


    /**
     * Сохраняет лог при ошибке query запроса
     *
     * @param array<QueryLogDto> $dto
     * @param mixed $result
     * @param string|null $cacheKey
     * @param bool|null $isCached
     * @param bool|null $isFromCache
     * @return void
     */
    protected function failQueryLog(
        array $arrayQueryLogDto,
        Throwable $exception,
    ): void {
        foreach ($arrayQueryLogDto as $dto) {
            /** @var QueryLogDto $dto */
            $dto->status = QueryLogStatusEnum::Failed;
            $dto->isUpdated = config('laravel-helper.query_log.store_on_start') ;
            $dto->info = [
                ...$dto->info,
                'duration' => $dto->getDuration(),
                'memory' => $dto->getMemory(),
                'exception' => Helper::exceptionToArray($exception),
            ];

            $dto->dispatch();
        }
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
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($this);

            if (
                $tables
                && !app(LaravelHelperService::class)->checkIgnoreTables($tables)
                && ($withCache === true || is_integer($withCache))
            ) {
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
            $sql = sql($query, $bindings);
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($sql);

            if (
                $tables
                && !app(LaravelHelperService::class)->checkIgnoreTables($tables)
                && ($withCache === true || is_integer($withCache))
            ) {
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
     * @override
     * Выполняет оператор INSERT в базе данных с использованием кеша
     * @see parent::update()
     *
     * @param  string|array $query
     * @param  array $bindings
     * @return bool
     */
    // #[Override()]
    public function queryInsert($query = null, $bindings = [])
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileInsert($this->getQuery(), $query),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileInsert($this, $query),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Helper::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = match (true) {
                $this instanceof EloquentBuilder => parent::insert($query),
                $this instanceof QueryBuilder => parent::insert($query),
                $this instanceof Connection => parent::insert($query, $bindings),
                $this instanceof Builder => parent::insert($query),

                default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
            };
            $this->flushCache($query, $bindings);
            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(arrayQueryLogDto: $arrayQueryLogDto, exception: $exception);

            throw $exception;
        }

        $this->updateQueryLog(arrayQueryLogDto: $arrayQueryLogDto, result: $result, status: $status);

        return $result;
    }


    /**
     * @override
     * Выполняет оператор UPDATE в базе данных с использованием кеша
     * @see parent::update()
     *
     * @param  mixed $query
     * @param  array $bindings
     * @return int
     */
    // #[Override()]
    public function queryUpdate($query = null, $bindings = [])
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileUpdate($this->getQuery(), $query),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileUpdate($this, $query),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Helper::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = match (true) {
                $this instanceof EloquentBuilder => parent::update($query),
                $this instanceof QueryBuilder => parent::update($query),
                $this instanceof Connection => parent::update($query, $bindings),
                $this instanceof Builder => parent::update($query),

                default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
            };
            $this->flushCache($query, $bindings);
            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(arrayQueryLogDto: $arrayQueryLogDto, exception: $exception);

            throw $exception;
        }

        $this->updateQueryLog(arrayQueryLogDto: $arrayQueryLogDto, result: $result, status: $status);

        return $result;
    }


    /**
     * @override
     * Выполняет оператор UPDATE в базе данных с использованием кеша
     * @see parent::update()
     *
     * @param  mixed $query
     * @param  array $bindings
     * @return int
     */
    // #[Override()]
    public function queryDelete($query = null, $bindings = [])
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileDelete($this->getQuery()),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileDelete($this),
                    [...(Helper::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Helper::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = match (true) {
                $this instanceof EloquentBuilder => parent::delete(),
                $this instanceof QueryBuilder => parent::delete($query),
                $this instanceof Connection => parent::delete($query, $bindings),
                $this instanceof Builder => parent::delete(),

                default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
            };
            $this->flushCache($query, $bindings);
            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(arrayQueryLogDto: $arrayQueryLogDto, exception: $exception);

            throw $exception;
        }

        $this->updateQueryLog(arrayQueryLogDto: $arrayQueryLogDto, result: $result, status: $status);

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
        if (!config('laravel-helper.query_log.enabled')) {
            return;
        }

        $queryCacheService = app(QueryCacheService::class);
        $tables = match (true) {
            is_array($query) => $queryCacheService->getTablesFromSql(sql($this->toSql(), $this->getBindings())),
            $this instanceof EloquentBuilder => $queryCacheService->getTablesFromModels($this->getModels()),
            $this instanceof QueryBuilder => $queryCacheService->getTablesFromSql($query),
            $this instanceof Connection => $queryCacheService->getTablesFromSql($query),

            default => $queryCacheService->getTablesFromSql($query),
        };

        if (app(LaravelHelperService::class)->checkIgnoreTables($tables)) {
            return;
        }

        foreach ($tables as $table) {
            $queryCacheService->flush($table);
        }
    }
}
