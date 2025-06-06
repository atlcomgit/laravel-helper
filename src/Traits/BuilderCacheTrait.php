<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @method static|EloquentBuilder|QueryBuilder withCache(?int $seconds = null)
 * @mixin \Illuminate\Database\Eloquent\Builder, \Illuminate\Database\Query\Builder
 */
trait BuilderCacheTrait
{
    /** Флаг кеширования запроса */
    protected int|bool|null $useWithCache = false;


    /**
     * Вызывает макрос подключения кеша
     *
     * @param int|bool|null|null $seconds
     * @return static
     */
    public function withCache(int|bool|null $seconds = null): static
    {
        $this->setUseWithCache($seconds);

        return $this;
    }


    /**
     * Устанавливает флаг подключения кеширования
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
     * Возвращает флаг подключения кеширования
     *
     * @return int|bool|null
     */
    public function getUseWithCache(): int|bool|null
    {
        return $this->useWithCache;
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
    public function getWithCache($columns = ['*']): Collection
    {
        $withCache = $this->getUseWithCache();
        $queryCacheService = app(QueryCacheService::class);
        $tables = $queryCacheService->getTablesFromModels(
            $this instanceof EloquentBuilder ? $this->getModels() : [$this]
        );
        $tableCache = config('cache.stores.database.table', 'cache');

        if ($tables && !in_array($tableCache, $tables) && ($withCache === true || is_integer($withCache))) {
            $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($withCache)]);
            $hasCache = $queryCacheService->hasQueryCache(tags: $tags, builder: $this);
            $result = $hasCache
                ? $queryCacheService->getQueryCache(tags: $tags, builder: $this)
                : parent::get($columns);

            $hasCache ?: $queryCacheService->setQueryCache(
                tags: $tags,
                builder: $this,
                value: $result,
                ttl: $withCache,
            );

            !($result instanceof Collection)
                ?: $result->map(
                    fn (/** @var \Atlcom\LaravelHelper\Defaults\DefaultModel $item */ $item) => match (true) {
                        (($item instanceof Model) && method_exists($item, 'setFromCached'))
                        => $item->setCached(!$hasCache)->setFromCached($hasCache),

                        default => $item,
                    }
                );
        } else {
            $result = parent::get($columns);
        }

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
    public function selectWithCache($query, $bindings = [], $useReadPdo = true): Collection|array
    {
        $withCache = $this->getUseWithCache();
        $queryCacheService = app(QueryCacheService::class);
        $tables = $queryCacheService->getTablesFromSql($query);
        $tableCache = config('cache.stores.database.table', 'cache');

        if ($tables && !in_array($tableCache, $tables) && ($withCache === true || is_integer($withCache))) {
            $tags = $queryCacheService->getQueryTags(...[...$tables, $this->getTagTtl($withCache)]);
            $hasCache = $queryCacheService->hasQueryCache(tags: $tags, builder: sql($query, $bindings));
            $result = $hasCache
                ? $queryCacheService->getQueryCache(tags: $tags, builder: sql($query, $bindings))
                : parent::select($query, $bindings, $useReadPdo);

            $hasCache ?: $queryCacheService->setQueryCache(
                tags: $tags,
                builder: sql($query, $bindings),
                value: $result,
                ttl: $withCache,
            );

            !($result instanceof Collection)
                ?: $result->map(
                    fn (/** @var \Atlcom\LaravelHelper\Defaults\DefaultModel $item */ $item) => match (true) {
                        (($item instanceof Model) && method_exists($item, 'setFromCached'))
                        => $item->setCached(!$hasCache)->setFromCached($hasCache),

                        default => $item,
                    }
                );
        } else {
            $result = parent::select($query, $bindings, $useReadPdo);
            ;
        }

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
}
