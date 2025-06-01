<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @mixin \Illuminate\Database\Eloquent\Builder, \Illuminate\Database\Query\Builder
 */
trait BuilderCacheTrait
{
    /** Включает кеширование запроса */
    protected int|bool|null $useWithCache = false;


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

        if ($withCache === true || is_integer($withCache)) {
            $queryCacheService = app(QueryCacheService::class);
            $tags = $queryCacheService->getQueryTags(...$this->getModels());
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
                    fn ($item) => match (true) {
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


    //?!? delete
    /**
     * @override
     * Выполняет запрос и возвращает первую запись с использованием кеша
     * @see parent::first()
     *
     * @param  array|string  $columns
     * @return TValue|null
     */
    // #[Override()]
    // public function firstWithCache($columns = ['*']): ?Model
    // {
    //     $withCache = $this->getUseWithCache();

    //     if ($withCache === true || is_integer($withCache)) {
    //         $queryCacheService = app(QueryCacheService::class);
    //         $tags = $queryCacheService->getQueryTags(__FUNCTION__, ...$this->getModels());
    //         $hasCache = $queryCacheService->hasQueryCache(tags: $tags, builder: $this);
    //         $result = $hasCache
    //             ? $queryCacheService->getQueryCache(tags: $tags, builder: $this)
    //             : parent::first($columns);

    //         $hasCache ?: $queryCacheService->setQueryCache(
    //             tags: $tags,
    //             builder: $this,
    //             value: $result,
    //             ttl: $withCache,
    //         );

    //         !($result instanceof Model && method_exists($result, 'setFromCached'))
    //             ?: $result->setCached(true)->setFromCache($hasCache);
    //     } else {
    //         $result = parent::first($columns);
    //     }

    //     return $result;
    // }


    /**
     * Сбрасывает кеш моделей из конструктора
     *
     * @return void
     */
    public function flushCache(): void
    {
        $queryCacheService = app(QueryCacheService::class);

        foreach ($this->getModels() as $model) {
            $queryCacheService->flush($model);
        }
    }
}
