<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
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
 * Трейт CRUD-операций с поддержкой кеширования и логирования
 *
 * Переопределяет стандартные методы конструктора запросов (get, select, insert,
 * create, update, delete, truncate) для интеграции с системами кеширования,
 * логирования и наблюдения за моделями.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait QueryOperationsTrait
{
    /**
     * Выполняет запрос как оператор «select» с использованием кеша
     *
     * @see parent::get()
     *
     * @param array|string $columns
     * @return Collection<int, TModel>
     */
    public function queryGet($columns = ['*']): Collection
    {
        try {
            $status = false;
            $this->syncQueryProperties();
            $queryCacheService = app(QueryCacheService::class);
            $tables = $queryCacheService->getTablesFromModels(
                [$this],
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
                    || ($this->withQueryCache !== false
                        && Lh::config(ConfigEnum::QueryCache, 'global'))
                )
            ) {
                $this->setQueryCacheClass($this::class);

                if ($this->getQueryCacheClass() === $this::class) {
                    $tags = $queryCacheService->getQueryTags(
                        ...[...$tables, $this->getTagTtl($this->withQueryCache)],
                    );
                    $cacheKey = $queryCacheService->getQueryKey(
                        tags: $tags,
                        builder: $this,
                    );
                    $hasCache = $queryCacheService->hasQueryCache(
                        tags: $tags,
                        key: $cacheKey,
                    );
                    $result = $hasCache
                        ? (
                            $queryCacheService->getQueryCache(
                                tags: $tags,
                                key: $cacheKey,
                            )
                            ?? parent::get($columns)
                        )
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
                            fn ($item) => match (true) {
                                (($item instanceof Model)
                                    && method_exists($item, 'setFromCached'))
                                => $item
                                    ->setCached($isCached)
                                    ->setFromCached($isFromCache),

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

        $this->updateQueryLog(
            $arrayQueryLogDto,
            $result,
            $cacheKey,
            $isCached,
            $isFromCache,
            $status,
        );

        return $result;
    }


    /**
     * Выполняет запрос как оператор «select» с использованием кеша
     *
     * @see parent::select()
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return Collection<int, stdClass>|array<int, stdClass>
     */
    public function querySelect(
        $query,
        $bindings = [],
        $useReadPdo = true,
    ): Collection|array {
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
                    || ($this->withQueryCache !== false
                        && Lh::config(ConfigEnum::QueryCache, 'global'))
                )
                && ($this->getQueryCacheClass() === $this::class
                    || $this instanceof Connection)
            ) {
                $tags = $queryCacheService->getQueryTags(
                    ...[...$tables, $this->getTagTtl($this->withQueryCache)],
                );
                $cacheKey = $queryCacheService->getQueryKey(
                    tags: $tags,
                    builder: $sql,
                );
                $hasCache = $queryCacheService->hasQueryCache(
                    tags: $tags,
                    key: $cacheKey,
                );
                $result = $hasCache
                    ? (
                        $queryCacheService->getQueryCache(
                            tags: $tags,
                            key: $cacheKey,
                        )
                        ?? parent::select($query, $bindings, $useReadPdo)
                    )
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
                        fn ($item) => match (true) {
                            (($item instanceof Model)
                                && method_exists($item, 'setFromCached'))
                            => $item
                                ->setCached($isCached)
                                ->setFromCached($isFromCache),

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

        $this->updateQueryLog(
            $arrayQueryLogDto,
            $result,
            $cacheKey,
            $isCached,
            $isFromCache,
            $status,
        );

        return $result;
    }


    /**
     * Выполняет оператор INSERT в базе данных с использованием кеша
     *
     * @see parent::insert()
     *
     * @param string|array $query
     * @param array $bindings
     * @return bool
     */
    public function queryInsert($query = null, $bindings = [])
    {
        $arrayQueryLogDto = [];

        try {
            $status = false;
            $this->syncQueryProperties();
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileInsert(
                        $this->getQuery(),
                        $query,
                    ),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileInsert($this, $query),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof Connection => is_array($query)
                ? null
                : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = is_null($sql)
                ? []
                : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings, ) {
                $result = match (true) {
                    $this instanceof EloquentBuilder
                    => parent::insert($query),
                    $this instanceof QueryBuilder
                    => parent::insert($query),
                    $this instanceof Connection
                    => parent::insert($query, $bindings),
                    $this instanceof Builder
                    => parent::insert($query),

                    default => throw new Exception(
                        'Конструктор запроса не определен в '
                        . __FUNCTION__,
                    ),
                };

                $this->clearCache($query, $bindings);

                $ids = $this->observeModelLog(
                    ModelLogTypeEnum::Create,
                    $query,
                    $bindings,
                );
                !($ids && $arrayQueryLogDto)
                    ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                return $result;
            };
            $result = (false && !isTesting()
                && DB::transactionLevel() === 0)
                ? DB::transaction($callback)
                : $callback();

            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(
                arrayQueryLogDto: $arrayQueryLogDto,
                exception: $exception,
            );

            throw $exception;
        }

        $this->updateQueryLog(
            arrayQueryLogDto: $arrayQueryLogDto,
            result: $result,
            status: $status,
        );

        return $result;
    }


    /**
     * Выполняет оператор INSERT (create) в базе данных с использованием кеша
     *
     * @see parent::create()
     *
     * @param array $attributes
     * @return TModel
     */
    public function queryCreate($attributes = null)
    {
        $arrayQueryLogDto = [];

        try {
            $status = false;
            $this->syncQueryProperties();
            $insertAttributes = is_array($attributes) && array_is_list($attributes)
                ? $attributes
                : [$attributes];
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileInsert(
                        $this->getQuery(),
                        $insertAttributes,
                    ),
                    $attributes,
                ),

                default => Hlp::castToString($attributes),
            };

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$attributes, ) {
                $result = match (true) {
                    $this instanceof EloquentBuilder
                    => parent::create($attributes),

                    default => throw new Exception(
                        'Конструктор запроса не определен в '
                        . __FUNCTION__,
                    ),
                };

                $this->clearCache($attributes);

                $ids = $this->observeModelLog(
                    ModelLogTypeEnum::Create,
                    $attributes,
                );
                !($ids && $arrayQueryLogDto)
                    ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                return $result;
            };
            $result = (false && !isTesting()
                && DB::transactionLevel() === 0)
                ? DB::transaction($callback)
                : $callback();

            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(
                arrayQueryLogDto: $arrayQueryLogDto,
                exception: $exception,
            );

            throw $exception;
        }

        $this->updateQueryLog(
            arrayQueryLogDto: $arrayQueryLogDto,
            result: $result,
            status: $status,
        );

        return $result;
    }


    /**
     * Выполняет оператор UPDATE в базе данных с использованием кеша
     *
     * @see parent::update()
     *
     * @param mixed $query
     * @param array $bindings
     * @return int
     */
    public function queryUpdate($query = null, $bindings = [])
    {
        try {
            $status = false;
            $this->syncQueryProperties();
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileUpdate(
                        $this->getQuery(),
                        $query,
                    ),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileUpdate($this, $query),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof Connection => is_array($query)
                ? null
                : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = is_null($sql)
                ? []
                : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings, ) {
                $ids = $this->observeModelLog(
                    ModelLogTypeEnum::Update,
                    $query,
                    $bindings,
                );
                !($ids && $arrayQueryLogDto)
                    ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder
                    => parent::update($query),
                    $this instanceof QueryBuilder
                    => parent::update($query),
                    $this instanceof Connection
                    => parent::update($query, $bindings),
                    $this instanceof Builder
                    => parent::update($query),

                    default => throw new Exception(
                        'Конструктор запроса не определен в '
                        . __FUNCTION__,
                    ),
                };

                $this->clearCache($query, $bindings);

                return $result;
            };
            $result = (false && !isTesting()
                && DB::transactionLevel() === 0)
                ? DB::transaction($callback)
                : $callback();

            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(
                arrayQueryLogDto: $arrayQueryLogDto,
                exception: $exception,
            );

            throw $exception;
        }

        $this->updateQueryLog(
            arrayQueryLogDto: $arrayQueryLogDto,
            result: $result,
            status: $status,
        );

        return $result;
    }


    /**
     * Выполняет оператор DELETE в базе данных с использованием кеша
     *
     * @see parent::delete()
     *
     * @param mixed $query
     * @param array $bindings
     * @param bool $isSoftDelete
     * @return int
     */
    public function queryDelete(
        $query = null,
        $bindings = [],
        bool $isSoftDelete = false,
    ) {
        try {
            $status = false;
            $this->syncQueryProperties();
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    $this->getGrammar()->compileDelete(
                        $this->getQuery(),
                    ),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof QueryBuilder => sql(
                    $this->getGrammar()->compileDelete($this),
                    [
                        ...(Hlp::castToArray($query) ?? []),
                        ...$this->getBindings(),
                    ],
                ),
                $this instanceof Connection => is_array($query)
                ? null
                : sql($query, $bindings),
                $this instanceof Builder => sql($query, $bindings),

                default => Hlp::castToString($query),
            };

            $arrayQueryLogDto = ($isSoftDelete || is_null($sql))
                ? []
                : $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$query, &$bindings, &$isSoftDelete, ) {
                $ids = $isSoftDelete
                    ? null
                    : $this->observeModelLog(
                        $isSoftDelete
                        ? ModelLogTypeEnum::SoftDelete
                        : ModelLogTypeEnum::Delete,
                        $query,
                        $bindings,
                    );
                !($ids && $arrayQueryLogDto)
                    ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder => $isSoftDelete
                    ? parent::delete()
                    : parent::forceDelete(),
                    $this instanceof QueryBuilder
                    => parent::delete($query),
                    $this instanceof Connection
                    => parent::delete($query, $bindings),
                    $this instanceof Builder
                    => parent::delete($query),

                    default => throw new Exception(
                        'Конструктор запроса не определен в '
                        . __FUNCTION__,
                    ),
                };

                $this->clearCache($query, $bindings);

                return $result;
            };
            $result = (false && !isTesting()
                && DB::transactionLevel() === 0)
                ? DB::transaction($callback)
                : $callback();

            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(
                arrayQueryLogDto: $arrayQueryLogDto,
                exception: $exception,
            );

            throw $exception;
        }

        $this->updateQueryLog(
            arrayQueryLogDto: $arrayQueryLogDto,
            result: $result,
            status: $status,
        );

        return $result;
    }


    /**
     * Выполняет оператор TRUNCATE в базе данных с использованием кеша
     *
     * @return int
     */
    public function queryTruncate()
    {
        try {
            $status = false;
            $this->syncQueryProperties();
            $sql = match (true) {
                $this instanceof EloquentBuilder => sql(
                    array_keys(
                        $this->getGrammar()->compileTruncate(
                            $this->getQuery(),
                        ),
                    )[0] ?? null,
                    $this->getBindings(),
                ),
                $this instanceof QueryBuilder => sql(
                    array_keys(
                        $this->getGrammar()->compileTruncate($this),
                    )[0] ?? null,
                    $this->getBindings(),
                ),

                default => null,
            } ?? "truncate table {$this->from}";

            $arrayQueryLogDto = $this->createQueryLog($sql);

            $callback = function () use (&$arrayQueryLogDto, &$sql) {
                $ids = $this->observeModelLog(
                    ModelLogTypeEnum::Truncate,
                    Hlp::sqlTables($sql),
                );
                !($ids && $arrayQueryLogDto)
                    ?: $arrayQueryLogDto[0]->info['ids'] = $ids;

                $result = match (true) {
                    $this instanceof EloquentBuilder
                    => parent::truncate(),
                    $this instanceof QueryBuilder
                    => parent::truncate(),
                    $this instanceof Connection
                    => parent::truncate(),
                    $this instanceof Builder
                    => parent::truncate(),

                    default => throw new Exception(
                        'Конструктор запроса не определен в '
                        . __FUNCTION__,
                    ),
                };

                $this->clearCache();

                return $result;
            };
            $result = (false && !isTesting()
                && DB::transactionLevel() === 0)
                ? DB::transaction($callback)
                : $callback();

            $status = true;

        } catch (Throwable $exception) {
            $this->failQueryLog(
                arrayQueryLogDto: $arrayQueryLogDto,
                exception: $exception,
            );

            throw $exception;
        }

        $this->updateQueryLog(
            arrayQueryLogDto: $arrayQueryLogDto,
            result: $result,
            status: $status,
        );

        return $result;
    }


    /**
     * Сбрасывает кеш моделей из конструктора
     *
     * @param string|array|null $query
     * @param array $bindings
     * @return void
     */
    public function clearCache($query = null, $bindings = []): void
    {
        if (!Lh::config(ConfigEnum::QueryLog, 'enabled')) {
            return;
        }

        $queryCacheService = app(QueryCacheService::class);
        $tables = match (true) {
            is_array($query) => $queryCacheService->getTablesFromSql(
                sql($this->toSql(), $this->getBindings()),
            ),
            $this instanceof EloquentBuilder
            => $queryCacheService->getTablesFromModels([$this]),
            $this instanceof QueryBuilder
            => $queryCacheService->getTablesFromSql($query) ?: [$this->from],
            $this instanceof Connection
            => $queryCacheService->getTablesFromSql($query),

            default => $queryCacheService->getTablesFromSql($query),
        };

        if (Lh::isFoundIgnoreTables($tables)) {
            return;
        }

        foreach ($tables as $table) {
            $queryCacheService->clearQueryCache($table);
        }
    }
}
