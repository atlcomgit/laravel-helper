<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
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
 * 
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutCache(int|bool|null $seconds = null)
 * 
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutLog(?bool $enabled = null)
 * 
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Connection
 */
trait QueryTrait
{
    /** Флаг включения кеширования запроса или ttl */
    protected int|bool|null $withQueryCache = null;
    /** Флаг включения лога query запроса */
    protected bool|null $withQueryLog = null;
    /** Флаг включения лога модели */
    protected bool|null $withModelLog = null;

    private string|null $withQueryCacheClass = null;
    private string|null $withQueryLogClass = null;


    /**
     * Устанавливает флаг включения кеширования
     *
     * @param int|string|bool|null $seconds
     * @param int|bool|null $seconds - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withQueryCache(int|string|bool|null $seconds = null): static
    {
        $now = now()->setTime(0, 0, 0, 0);
        !is_string($seconds) ?: $seconds = (int)abs(
            $now->copy()->modify(trim((string)$seconds, '- '))->diffInSeconds($now),
        );
        $this->setQueryCache($seconds ?? true);
        ($seconds === false) ?: $this->setQueryCacheClass(null, true);

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
        $this->setQueryLog($enabled ?? true);
        ($enabled === false) ?: $this->setQueryLogClass(null, true);

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
        $this->setModelLog($enabled ?? true);

        return $this;
    }


    /**
     * Устанавливает флаг включения кеша query по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param int|string|bool|null $seconds
     * @return void
     */
    public function setQueryCache(int|string|bool|null $seconds): void
    {
        $this->withQueryCache = $seconds;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryCache($this->withQueryCache);
            // $this->getQuery()->getConnection()->setQueryCache($this->withQueryCache);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryCache($this->withQueryCache);
        }
    }


    /**
     * Устанавливает флаг включения лога query по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param bool|null $enabled
     * @return void
     */
    public function setQueryLog(bool|null $enabled): void
    {
        $this->withQueryLog = $enabled;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryLog($this->withQueryLog);
            // $this->getQuery()->getConnection()->setQueryLog($this->withQueryLog);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryLog($this->withQueryLog);
        }
    }


    /**
     * Устанавливает флаг включения лога модели по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param bool|null $enabled
     * @return void
     */
    public function setModelLog(bool|null $enabled): void
    {
        $this->withModelLog = $enabled;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setModelLog($this->withModelLog);
            // $this->getQuery()->getConnection()->setModelLog($this->withModelLog);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setModelLog($this->withModelLog);
        }
    }


    /**
     * Синхронизирует флаги по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @return void
     */
    public function syncQueryProperties(): void
    {
        $this->setQueryCache($this->withQueryCache);
        $this->setQueryLog($this->withQueryLog);
        $this->setModelLog($this->withModelLog);
    }


    /**
     * Устанавливает класс вызвавший кеш первого query запроса
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string|null $class
     * @return static
     */
    public function setQueryCacheClass(?string $class, bool $reset = false): static
    {
        !(is_null($this->withQueryCacheClass) || $reset) ?: $this->withQueryCacheClass = $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryCacheClass($this->withQueryCacheClass, $reset);
            // $this->getQuery()->getConnection()->setQueryCacheClass($this->withQueryCacheClass, $reset);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryCacheClass($this->withQueryCacheClass, $reset);
        }

        return $this;
    }


    /**
     * Устанавливает класс вызвавший лог первого query запроса
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string|null $class
     * @return static
     */
    public function setQueryLogClass(?string $class, bool $reset = false): static
    {
        !(is_null($this->withQueryLogClass) || $reset) ?: $this->withQueryLogClass = $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryLogClass($this->withQueryLogClass, $reset);
            // $this->getQuery()->getConnection()->setQueryLogClass($this->withQueryLogClass);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryLogClass($this->withQueryLogClass, $reset);
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
            is_null($ttl) || $ttl === 0 => 'ttl_not_set',
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",

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
            Lh::config(ConfigEnum::ConsoleLog, 'table'),
            Lh::config(ConfigEnum::HttpLog, 'table'),
            Lh::config(ConfigEnum::ModelLog, 'table'),
            Lh::config(ConfigEnum::ProfilerLog, 'table'),
            Lh::config(ConfigEnum::RouteLog, 'table'),
            Lh::config(ConfigEnum::QueryLog, 'table'),
            Lh::config(ConfigEnum::QueueLog, 'table'),
            Lh::config(ConfigEnum::ViewLog, 'table'),
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
        $exceedDuration = 0;

        if (
            !($enabled = Lh::config(ConfigEnum::QueryLog, 'enabled'))
            || !(
                $this->withQueryLog === true
                || ($this->withQueryLog !== false && Lh::config(ConfigEnum::QueryLog, 'global'))
            )
        ) {
            // Лог запроса выключен, проверяем время превышения
            $exceedDuration = (int)Lh::config(ConfigEnum::QueryLog, 'timer_exceed');

            // Если сервис лога запросов выключен или время превышения не задано, то выходим
            if (!$enabled || $exceedDuration <= 0) {
                return $result;
            }
        }

        $this->setQueryLogClass($this::class);

        if ($this->getQueryLogClass() !== $this::class) {
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
                    'query_length' => Hlp::stringLength($sql),
                    ...(Lh::config(ConfigEnum::App, 'debug_trace')
                        ? [
                            'trace' => Lh::config(ConfigEnum::App, 'debug_trace_vendor')
                                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                                : Hlp::arrayExcludeTraceVendor(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
                        ]
                        : []
                    ),
                    'exceed_duration' => $exceedDuration,
                ],
            );

            if (Lh::canDispatch($dto)) {
                !(Lh::config(ConfigEnum::QueryLog, 'store_on_start') && !$exceedDuration) ?: $dto->dispatch();
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
            $dto->isUpdated = Lh::config(ConfigEnum::QueryLog, 'store_on_start');
            $dto->duration = $dto->getDuration();
            $dto->memory = $dto->getMemory();
            $dto->count = match (true) {
                $result instanceof Collection => $result->count(),
                $result instanceof Model => 1,
                is_array($result) => count($result),

                default => Hlp::castToInt((bool)$result),
            };
            $dto->info = [
                ...($dto->info ?? []),
                'duration' => Hlp::timeSecondsToString(value: $dto->duration, withMilliseconds: true),
                'memory' => Hlp::sizeBytesToString($dto->memory),
                'count' => Hlp::stringPlural($dto->count, ['записей', 'запись', 'записи']),
                // 'result_length' => Hlp::stringLength(serialize($result)),
                'result_type' => match (true) {
                    is_object($result) => $result::class,

                    default => gettype($result)
                },
            ];

            // Установленное время превышения запроса
            $exceedDuration = $dto->info['exceed_duration'] ?: 0;

            // Если включен лог запроса или запрос превысил установленное время
            if (!$exceedDuration || (int)($dto->duration * 1000) >= $exceedDuration) {
                $dto->dispatch();
            }
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
            $dto->isUpdated = Lh::config(ConfigEnum::QueryLog, 'store_on_start');
            $dto->duration = $dto->getDuration();
            $dto->memory = $dto->getMemory();
            $dto->info = [
                ...($dto->info ?? []),
                'duration' => Hlp::timeSecondsToString(value: $dto->duration, withMilliseconds: true),
                'memory' => Hlp::sizeBytesToString($dto->memory),
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
            $this->syncQueryProperties();
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromModels(
                [$this] // $this instanceof EloquentBuilder ? $this->getModels() : [$this]
            );
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($this);

            if (
                $tables
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                && (Lh::notFoundIgnoreTables($tables))
                && (
                    $this->withQueryCache === true
                    || is_integer($this->withQueryCache)
                    || ($this->withQueryCache !== false && Lh::config(ConfigEnum::QueryCache, 'global'))
                )
            ) {
                $this->setQueryCacheClass($this::class);

                if ($this->getQueryCacheClass() === $this::class) {
                    $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($this->withQueryCache)]);
                    $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $this);
                    $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                    $result = $hasCache
                        ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                        : parent::get($columns);

                    $hasCache ?: $isCached = $queryCacheService->setQueryCache(
                        tags: $tags,
                        key: $cacheKey,
                        value: $result,
                        ttl: $this->withQueryCache,
                    );
                    $isCached ??= false;
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
            $this->syncQueryProperties();
            $queryCacheService = app(QueryCacheService::class);
            $sql = sql($query, $bindings);
            $tables = $queryCacheService->getTablesFromSql($sql);
            $cacheKey = $isCached = $isFromCache = null;

            $arrayQueryLogDto = $this->createQueryLog($sql);
            $this->setQueryCacheClass($this::class);

            if (
                $tables
                && (Lh::notFoundIgnoreTables($tables))
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                && (
                    $this->withQueryCache === true
                    || is_integer($this->withQueryCache)
                    || ($this->withQueryCache !== false && Lh::config(ConfigEnum::QueryCache, 'global'))
                )
                && ($this->getQueryCacheClass() === $this::class || $this instanceof Connection)
            ) {
                $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($this->withQueryCache)]);
                $cacheKey = $queryCacheService->getQueryKey(tags: $tags, builder: $sql);
                $hasCache = $queryCacheService->hasQueryCache(tags: $tags, key: $cacheKey);
                $result = $hasCache
                    ? $queryCacheService->getQueryCache(tags: $tags, key: $cacheKey)
                    : parent::select($query, $bindings, $useReadPdo);

                $hasCache ?: $isCached = $queryCacheService->setQueryCache(
                    tags: $tags,
                    key: $cacheKey,
                    value: $result,
                    ttl: $this->withQueryCache,
                );
                $isCached ??= false;
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
                $this instanceof Connection => is_array($query) ? null : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = is_null($sql) ? [] : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings) {
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
            };
            $result = (false && !isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();

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

            $callback = function () use (&$arrayQueryLogDto, &$attributes) {
                $result = match (true) {
                    $this instanceof EloquentBuilder => parent::create($attributes),

                    default => throw new Exception('Конструктор запроса не определен в ' . __FUNCTION__),
                };

                $this->flushCache($attributes);

                $ids = $this->observeModelLog(ModelLogTypeEnum::Create, $attributes);
                !($ids && $arrayQueryLogDto) ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                return $result;
            };
            $result = (false && !isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();

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
                $this instanceof Connection => is_array($query) ? null : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = is_null($sql) ? [] : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings) {
                $ids = $this->observeModelLog(ModelLogTypeEnum::Update, $query, $bindings);
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
            };
            $result = (false && !isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();

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
                $this instanceof Connection => is_array($query) ? null : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = ($isSoftDelete || is_null($sql)) ? [] : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings, &$isSoftDelete) {
                $ids = $isSoftDelete
                    ? null
                    : $this->observeModelLog(
                        $isSoftDelete ? ModelLogTypeEnum::SoftDelete : ModelLogTypeEnum::Delete,
                        $query,
                        $bindings,
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
            };
            $result = (false && !isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();

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

            $callback = function () use (&$arrayQueryLogDto, &$sql) {
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
            };
            $result = (false && !isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();

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
        if (!Lh::config(ConfigEnum::QueryLog, 'enabled')) {
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

        if (Lh::isFoundIgnoreTables($tables)) {
            return;
        }

        foreach ($tables as $table) {
            $queryCacheService->flushQueryCache($table);
        }
    }


    /**
     * Запускает методы observer
     *
     * @param ModelLogTypeEnum $type
     * @param array|string|int|null $attributes
     * @param array|null $bindings
     * @return array
     */
    public function observeModelLog(ModelLogTypeEnum $type, $attributes = null, $bindings = null): array
    {
        $result = [];

        if (
            $this instanceof EloquentBuilder
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog !== false && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $observer = app(ModelLogObserver::class);

            $models = match ($type) {
                ModelLogTypeEnum::Create => [$this->getModel()],

                default => $this->getModels() ?: [$this->getModel()],
            };

            foreach ($models as $model) {
                if ($model && $model instanceof Model) {
                    if (
                        method_exists($model, 'isWithModelLog')
                        && ($model->withModelLog === true || $this->withModelLog === true)
                        && method_exists($model, 'withModelLog')
                    ) {
                        (is_null($this->withModelLog) || !is_null($model->withModelLog))
                            ?: $model->withModelLog = $this->withModelLog;

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
            $this->getQuery()->getConnection()->withModelLog(false);
        }

        if (
            $this instanceof Connection && is_string($attributes)
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog !== false && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $withModelLog = $this->withModelLog;
            $observer = app(ModelLogObserver::class);
            $sql = sql($attributes, $bindings ?? []);
            $table = Hlp::arrayFirst(Hlp::sqlTables($attributes));
            $modelClass = Lh::getModelClassByTable($table);

            switch ($type) {
                case ModelLogTypeEnum::Create:
                    $fields = Hlp::sqlFieldsInsert($attributes);
                    $attributes = array_combine(
                        $fields,
                        array_slice(array_pad($bindings ?? [], count($fields), null), 0, count($fields)),
                    );
                    break;

                case ModelLogTypeEnum::Update:
                case ModelLogTypeEnum::SoftDelete:
                    $fields = Hlp::sqlFieldsUpdate($attributes);
                    $attributes = array_combine(
                        $fields,
                        array_slice(array_pad($bindings ?? [], count($fields), null), 0, count($fields)),
                    );
                    break;

                default:
                    $fields = array_keys(
                        Hlp::arrayUnDot(Hlp::arrayFlip(Hlp::sqlFields($attributes, false)))[$table] ?? []
                    );
                    $attributes = array_combine(
                        $fields,
                        array_slice(array_pad($bindings ?? [], count($fields), null), 0, count($fields)),
                    );
            }

            if ($modelClass) {
                switch ($type) {
                    case ModelLogTypeEnum::Create:
                        $primaryKey = (new $modelClass())->getKeyName();
                        $model = (new $modelClass())->fill($attributes);
                        $modelId = $this->getLastInsertId();
                        $model->{$primaryKey} = $model->getKeyType() === 'int' ? (int)$modelId : (string)$modelId;
                        $models = [$model];
                        break;

                    default:
                        try {
                            $primaryKey = (new $modelClass())->getKeyName();
                            $models = DB::table($table)
                                ->when($this->withQueryLog, static fn ($q, $v) => $q->withQueryLog($v))
                                ->when(
                                    Hlp::stringSplitRange($sql, [' where ', ' WHERE '], 1),
                                    static fn ($q, $v) => $q->whereRaw($v),
                                )
                                ->get()
                                ->map(
                                    static function ($item) use ($modelClass, $primaryKey) {
                                        $model = new $modelClass(Hlp::castToArray($item));
                                        $model->$primaryKey = $item->$primaryKey;

                                        return $model;
                                    }
                                );
                        } catch (Throwable $exception) {
                            try {
                                $whereAttributes = [];
                                foreach ($attributes as $column => $value) {
                                    $whereAttributes[$column] = match (true) {
                                        is_scalar($value) => $value,
                                        is_array($value) || is_object($value) => Hlp::castToArray($value),

                                        default => $value,
                                    };
                                }

                                $models = DB::table($table)
                                    ->when($this->withQueryLog, static fn ($q, $v) => $q->withQueryLog($v))
                                    ->where($whereAttributes)
                                    ->get()
                                    ->map(
                                        static function ($item) use ($modelClass, $primaryKey) {
                                            $model = new $modelClass(Hlp::castToArray($item));
                                            $model->$primaryKey = $item->$primaryKey;

                                            return $model;
                                        }
                                    );
                            } catch (Throwable $exception) {
                                $models = [];
                            }
                        }
                }

                foreach ($models as $model) {
                    if ($model && $model instanceof Model) {
                        if (method_exists($model, 'isWithModelLog') && method_exists($model, 'withModelLog')) {
                            (is_null($withModelLog) || !is_null($model->withModelLog))
                                ?: $model->withModelLog = $withModelLog;

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

                !method_exists($this, 'withModelLog') ?: $this->withModelLog(false);
            }
        }

        if (
            $type === ModelLogTypeEnum::Truncate && is_array($attributes)
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog !== false && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $withModelLog = $this->withModelLog;
            $observer = app(ModelLogObserver::class);

            foreach ($attributes as $table) {
                /** @var Model $modelClass */
                $modelClass = Lh::getModelClassByTable($table);
                if (!$modelClass) {
                    continue;
                }

                /** @var \Illuminate\Database\Eloquent\Collection $models */
                $models = (
                    method_exists($modelClass, 'withTrashed')
                ? $modelClass::query()->withTrashed()
                : $modelClass::query()
                )
                    ->when($this->withQueryLog, static fn ($q, $v) => $q->withQueryLog($v))
                    ->get();
                $primaryKey = with(new $modelClass)->getKeyName();
                !(($modelFirst = $models->first()) && $modelFirst->$primaryKey)
                    ?: $models->sortBy($modelFirst->$primaryKey);

                foreach ($models as $model) {
                    if ($model && $model instanceof Model) {
                        if (method_exists($model, 'isWithModelLog') && method_exists($model, 'withModelLog')) {
                            (is_null($withModelLog) || !is_null($model->withModelLog))
                                ?: $model->withModelLog = $withModelLog;

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
