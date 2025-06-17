<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @method static|EloquentBuilder|QueryBuilder withQueryCache(int|bool|null $seconds = null)
 * @method static|EloquentBuilder|QueryBuilder withQueryLog(?bool $enabled = null)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Connection
 */
trait QueryTrait
{
    /** Флаг включения кеширования запроса или ttl */
    protected int|bool|null $withQueryCache = false;
    /** Флаг включения лога query запроса */
    protected bool|null $withQueryLog = false;
    /** Флаг включения лога модели */
    protected bool|null $withModelLog = null;

    private string|null $withQueryCacheClass = null;
    private string|null $withQueryLogClass = null;


    /**
     * Устанавливает флаг включения кеширования
     *
     * @param int|bool|null $seconds
     * @param int|bool|null $seconds - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withQueryCache(int|bool|null $seconds = null): static
    {
        $this->withQueryCache = $seconds ?? true;
        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->withQueryCache($this->withQueryCache);
            $this->getConnection()->withQueryCache($this->withQueryCache);
        }

        return $this;
    }


    /**
     * Устанавливает флаг включения лога query запроса
     *
     * @param bool|null $enabled
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withQueryLog(bool|null $enabled = null): static
    {
        $this->withQueryLog = $enabled ?? true;
        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->withQueryLog($this->withQueryLog);
            $this->getConnection()->withQueryLog($this->withQueryLog);
        }

        return $this;
    }


    /**
     * Устанавливает флаг включения лога модели
     *
     * @param bool|null $enabled
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withModelLog(bool|null $enabled = null): static
    {
        $this->withModelLog = $enabled ?? true;
        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->withModelLog($this->withModelLog);
            $this->getConnection()->withModelLog($this->withModelLog);
        }

        return $this;
    }


    /**
     * Устанавливает класс вызвавший кеш первого query запроса
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string $class
     * @return static
     */
    public function setQueryCacheClass(string $class): static
    {
        $this->withQueryCacheClass ??= $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryCacheClass($this->withQueryCacheClass);
            $this->getQuery()->getConnection()->setQueryCacheClass($this->withQueryCacheClass);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryCacheClass($this->withQueryCacheClass);
        }

        return $this;
    }


    /**
     * Устанавливает класс вызвавший лог первого query запроса
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string $class
     * @return static
     */
    public function setQueryLogClass(string $class): static
    {
        $this->withQueryLogClass ??= $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryLogClass($this->withQueryLogClass);
            $this->getQuery()->getConnection()->setQueryLogClass($this->withQueryLogClass);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryLogClass($this->withQueryLogClass);
        }

        return $this;
    }


    /**
     * Возвращает кешируемый класс
     *
     * @return string|null
     */
    public function getQueryCacheClass(): ?string
    {
        return $this->withQueryCacheClass;
    }


    /**
     * Возвращает логируемой класс
     *
     * @return string|null
     */
    public function getQueryLogClass(): ?string
    {
        return $this->withQueryLogClass;
    }


    /**
     * Возвращает название тега из ttl (дополнительно добавляется в ключ кеша)
     *
     * @param int|bool|null $ttl
     * @return string
     */
    protected function getTagTtl(int|bool|null $ttl): string
    {
        $ttl ??= $this->withQueryCache;

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
        $this->setQueryLogClass($this::class);

        if (
            !config('laravel-helper.query_log.enabled')
            || !$this->withQueryLog
            || $this->getQueryLogClass() !== $this::class
        ) {
            return $result;
        }

        $sql = app(QueryCacheService::class)->getSqlFromBuilder($builder);
        $models = [$this]; // $models = $this instanceof EloquentBuilder ? $this->getModels() : [$this];
        $classes = [];
        $ids = [];

        foreach ($models as $model) {
            $classes[$model::class] = true;
            !($model instanceof Model) ?: $ids[$model::class][] = $model->{$model->getKeyName()};
        }

        foreach (array_keys($classes) ?: [$this::class] as $class) {
            /** @var Model|QueryBuilder $model */
            $dto = QueryLogDto::create(
                name: Hlp::pathClassName($class),
                // modelId: $model instanceof Model ? $model->{$model->getKeyName()} : null,
                query: $sql,
                info: [
                    'class' => $class,
                    'tables' => Hlp::sqlTables($sql),
                    'fields' => Hlp::sqlFields($sql),
                    'ids' => $ids[$class] ?? null,
                    'size_query' => Hlp::stringLength($sql),
                    ...(config('laravel-helper.app.debug_trace')
                        ? [
                            'trace' => config('laravel-helper.app.debug_trace_vendor')
                                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                                : Hlp::arrayExcludeTraceVendor(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
                        ]
                        : []
                    ),
                ],
            );

            if (app(LaravelHelperService::class)->canDispatch($dto)) {
                !config('laravel-helper.query_log.store_on_start') ?: $dto->dispatch();
                $result[] = $dto;
            }
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
            $dto->isUpdated = config('laravel-helper.query_log.store_on_start');
            $dto->info = [
                ...($dto->info ?? []),
                'duration' => $dto->getDuration(),
                'memory' => $dto->getMemory(),
                'size_result' => Hlp::stringLength(json_encode($result, Hlp::jsonFlags()) ?: ''),
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
            $dto->isUpdated = config('laravel-helper.query_log.store_on_start');
            $dto->info = [
                ...($dto->info ?? []),
                'duration' => $dto->getDuration(),
                'memory' => $dto->getMemory(),
                'exception' => Hlp::exceptionToArray($exception),
            ];

            $dto->dispatch();
        }
    }


    /**
     * Выполняет запрос как оператор «select» с использованием кеша
     * @see parent::get()
     *
     * @param array|string  $columns
     * @return Collection<int, TModel>
     */
    public function queryGet($columns = ['*']): Collection
    {
        try {
            $status = false;
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromModels(
                [$this] // $this instanceof EloquentBuilder ? $this->getModels() : [$this]
            );
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($this);
            $this->setQueryCacheClass($this::class);

            if (
                $tables
                && (app(LaravelHelperService::class)->notFoundIgnoreTables($tables))
                && ($this->withQueryCache === true || is_integer($this->withQueryCache))
                && ($this->getQueryCacheClass() === $this::class)
            ) {
                $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($this->withQueryCache)]);
                $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $this);
                $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                $result = $hasCache
                    ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                    : parent::get($columns);

                $hasCache ?: $queryCacheService->setQueryCache(
                    tags: $tags,
                    key: $cacheKey,
                    value: $result,
                    ttl: $this->withQueryCache,
                );
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
     * Выполняет запрос как оператор «select» с использованием кеша
     * @see parent::select()
     *
     * @param string  $query
     * @param array  $bindings
     * @param bool  $useReadPdo
     * @return Collection<int, stdClass>|array<int, stdClass>
     */
    public function querySelect($query, $bindings = [], $useReadPdo = true): Collection|array
    {
        try {
            $status = false;
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromSql($query);
            $sql = sql($query, $bindings);
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($sql);
            $this->setQueryCacheClass($this::class);

            if (
                $tables
                && (app(LaravelHelperService::class)->notFoundIgnoreTables($tables))
                && ($this->withQueryCache === true || is_integer($this->withQueryCache))
                && ($this->getQueryCacheClass() === $this::class)
            ) {
                $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($this->withQueryCache)]);
                $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $sql);
                $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                $result = $hasCache
                    ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                    : parent::select($query, $bindings, $useReadPdo);

                $hasCache ?: $queryCacheService->setQueryCache(
                    tags: $tags,
                    key: $cacheKey,
                    value: $result,
                    ttl: $this->withQueryCache,
                );
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
     * Выполняет оператор INSERT в базе данных с использованием кеша
     * @see parent::insert()
     *
     * @param string|array $query
     * @param array $bindings
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
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileInsert($this, $query),
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = DB::transaction(function () use (&$arrayQueryLogDto, &$query, &$bindings) {
                $result = match (true) {
                    $this instanceof EloquentBuilder => parent::insert($query),
                    $this instanceof QueryBuilder => parent::insert($query),
                    $this instanceof Connection => parent::insert($query, $bindings),
                    $this instanceof Builder => parent::insert($query),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache($query, $bindings);

                $ids = $this->observeModelLog(ModelLogTypeEnum::Create, $query, $bindings);
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                return $result;
            });

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
     * Выполняет оператор INSERT в базе данных с использованием кеша
     * @see parent::create()
     *
     * @param array $attributes
     * @param array $bindings
     * @return TModel
     */
    // #[Override()]
    public function queryCreate($attributes = null)
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileInsert($this->getQuery(), $attributes),
                    $attributes,
                ),

                default => Hlp::castToString($attributes),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = DB::transaction(function () use (&$arrayQueryLogDto, &$attributes) {
                $result = match (true) {
                    $this instanceof EloquentBuilder => parent::create($attributes),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache($attributes);

                $ids = $this->observeModelLog(ModelLogTypeEnum::Create, $attributes);
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                return $result;
            });

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
     * @param mixed $query
     * @param array $bindings
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
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileUpdate($this, $query),
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = DB::transaction(function () use (&$arrayQueryLogDto, &$query, &$bindings) {
                $ids = $this->observeModelLog(ModelLogTypeEnum::Update, $query);
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder => parent::update($query),
                    $this instanceof QueryBuilder => parent::update($query),
                    $this instanceof Connection => parent::update($query, $bindings),
                    $this instanceof Builder => parent::update($query),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache($query, $bindings);

                return $result;
            });

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
     * @param mixed $query
     * @param array $bindings
     * @param bool $isSoftDelete
     * @return int
     */
    // #[Override()]
    public function queryDelete($query = null, $bindings = [], bool $isSoftDelete = false)
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileDelete($this->getQuery()),
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileDelete($this),
                    [...(Hlp::castToArray($query) ?? []), ...$this->getBindings()],
                ),
                $this instanceof Connection => sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = DB::transaction(function () use (&$arrayQueryLogDto, &$query, &$bindings, &$isSoftDelete) {
                $ids = $isSoftDelete
                    ? null
                    : $this->observeModelLog(
                        $isSoftDelete ? ModelLogTypeEnum::SoftDelete : ModelLogTypeEnum::Delete,
                        $query,
                    );
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder => $isSoftDelete ? parent::delete() : parent::forceDelete(),
                    $this instanceof QueryBuilder => parent::delete($query),
                    $this instanceof Connection => parent::delete($query, $bindings),
                    $this instanceof Builder => parent::delete($query),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache($query, $bindings);

                return $result;
            });

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
     * @return int
     */
    // #[Override()]
    public function queryTruncate()
    {
        try {
            $status = false;
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    array_keys($this->getGrammar()->compileTruncate($this->getQuery()))[0] ?? null,
                    $this->getBindings(),
                ),
                $this instanceof QueryBuilder => sql(
                    array_keys($this->getGrammar()->compileTruncate($this))[0] ?? null,
                    $this->getBindings(),
                ),

                default => null,
            } ?? "truncate table {$this->from}";

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $result = DB::transaction(function () use (&$arrayQueryLogDto, &$sql) {
                $ids = $this->observeModelLog(ModelLogTypeEnum::Truncate, Hlp::sqlTables($sql));
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder => parent::truncate(),
                    $this instanceof QueryBuilder => parent::truncate(),
                    $this instanceof Connection => parent::truncate(),
                    $this instanceof Builder => parent::truncate(),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache();

                return $result;
            });

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
     * @param string|array|null  $query
     * @param array  $bindings
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
            $this instanceof EloquentBuilder => $queryCacheService->getTablesFromModels([$this]), // $queryCacheService->getTablesFromModels($this->getModels()),
            $this instanceof QueryBuilder => $queryCacheService->getTablesFromSql($query) ?: [$this->from],
            $this instanceof Connection => $queryCacheService->getTablesFromSql($query),

            default => $queryCacheService->getTablesFromSql($query),
        };

        if (app(LaravelHelperService::class)->isFoundIgnoreTables($tables)) {
            return;
        }

        foreach ($tables as $table) {
            $queryCacheService->flush($table);
        }
    }


    /**
     * Запускает методы observer
     *
     * @param ModelLogTypeEnum $type
     * @param array|int|null $attributes
     * @param array|null $bindings
     * @return array
     */
    public function observeModelLog(ModelLogTypeEnum $type, $attributes = null, $bindings = null): array
    {
        $result = [];

        if ($this instanceof EloquentBuilder) {
            $observer = app(ModelLogObserver::class);

            $models = match ($type) {
                ModelLogTypeEnum::Create => [$this->getModel()],

                default => $this->getModels() ?: [$this->getModel()],
            };

            foreach ($models as $model) {
                if ($model && $model instanceof Model) {
                    if (
                        method_exists($model, 'isWithModelLog')
                        && ($model->isWithModelLog() === true || $this->withModelLog === true)
                        && method_exists($model, 'withModelLog')
                    ) {
                        is_null($this->withModelLog) ?: $model->withModelLog = $this->withModelLog;

                        match ($type) {
                            ModelLogTypeEnum::Create => $observer->created($model, $attributes),
                            ModelLogTypeEnum::Update => $observer->updated($model, $attributes),
                            ModelLogTypeEnum::Delete => $observer->deleted($model, $attributes),
                            ModelLogTypeEnum::SoftDelete => $observer->updated($model, $attributes),
                            ModelLogTypeEnum::ForceDelete => $observer->forceDeleted($model),
                            ModelLogTypeEnum::Restore => $observer->restored($model),

                            default => null,
                        };

                        $model->withModelLog = false;
                    }

                    $result[] = $model->{$model->getKeyName()};
                }
            }

            is_null($this->withModelLog) ?: $this->getQuery()->withModelLog($this->withModelLog);
        }

        /* not need
        if ($this instanceof Connection && is_string($attributes) && is_array($bindings)) {
            $observer = app(ModelLogObserver::class);
            $sql = sql($attributes, $bindings ?? []);
            $table = Hlp::arrayFirst(Hlp::sqlTables($sql));
            $fields = array_keys(Hlp::arrayUnDot(Hlp::arrayFlip(Hlp::sqlFields($sql)))[$table] ?? []);
            $modelClass = app(LaravelHelperService::class)->getModelClassByTable($table);
            $attributes = array_combine($fields, $bindings);
            $model = (new $modelClass())->fill($attributes);
            is_null($this->withModelLog) ?: $model->withModelLog = $this->withModelLog;

            match ($type) {
                ModelLogTypeEnum::Create => $observer->created($model, $attributes),
                ModelLogTypeEnum::Update => $observer->updated($model, $attributes),
                ModelLogTypeEnum::Delete => $observer->deleted($model, $attributes),
                ModelLogTypeEnum::SoftDelete => $observer->updated($model, $attributes),
                ModelLogTypeEnum::ForceDelete => $observer->forceDeleted($model),
                ModelLogTypeEnum::Restore => $observer->restored($model),

                default => null,
            };

            $model->withModelLog = false;
            !method_exists($this, 'withModelLog') ?: $this->withModelLog(false);
        }
        */

        if ($type === ModelLogTypeEnum::Truncate && $attributes) {
            $observer = app(ModelLogObserver::class);

            foreach ($attributes as $table) {
                /** @var Model $modelClass */
                $modelClass = app(LaravelHelperService::class)->getModelClassByTable($table);
                $models = $modelClass::query()->withTrashed()->orderBy(with(new $modelClass)->getKeyName())->get();

                foreach ($models as $model) {
                    if ($model && $model instanceof Model) {
                        if (
                            method_exists($model, 'isWithModelLog')
                            && method_exists($model, 'withModelLog')
                        ) {
                            is_null($this->withModelLog) ?: $model->withModelLog = $this->withModelLog;

                            $observer->truncated($model);

                            $model->withModelLog = false;
                        }

                        $result[] = $model->{$model->getKeyName()};
                    }
                }

            }
        }

        // not need for connection
        // if (!is_null($this->withModelLog) && $this instanceof QueryBuilder) {
        //     $this->getConnection()->withModelLog($this->withModelLog);
        // }

        return $result;
    }
}
