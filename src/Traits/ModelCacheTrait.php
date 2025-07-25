<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Observers\QueryCacheObserver;
use Atlcom\LaravelHelper\Services\QueryCacheService;

/**
 * Трейт для подключения кеширования модели
 * 
 * @property-read bool $isCached
 * @property-read bool $isFromCache
 * 
 * @method static|EloquentBuilder withQueryCache(int|bool|null $seconds = null)
 * @method static|EloquentBuilder withQueryLog(?bool $enabled = null)
 * @method static|EloquentBuilder setCached(bool $value)
 * @method static|EloquentBuilder setFromCached(bool $value)
 * @method static|EloquentBuilder flushCache()
 * @method bool isCached()
 * @method bool isFromCached()
 * 
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultModel
 * @mixin QueryTrait
 */
trait ModelCacheTrait
{
    protected bool $isCached = false;
    protected bool $isFromCache = false;


    /**
     * Автозагрузка трейта
     *
     * @return void
     */
    protected static function bootModelCacheTrait()
    {
        if (Lh::config(ConfigEnum::QueryCache, 'enabled')) {
            static::observe(QueryCacheObserver::class);
        }
    }


    /**
     * Создает новый конструктор запроса модели (Eloquent)
     * @see parent::newEloquentBuilder()
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    public function newEloquentBuilder($query)
    {
        $builder = new EloquentBuilder($query);
        $builder->getQuery()->setQueryCache(null);
        $builder->getQuery()->setQueryLog(null);
        $builder->getQuery()->setModelLog($this->withModelLog);

        return $builder;
    }


    /**
     * Вызывает макрос подключения кеша
     *
     * @param int|string|bool|null $seconds
     * @return EloquentBuilder<static>
     */
    public static function withQueryCache(int|string|bool|null $seconds = null): EloquentBuilder
    {
        $query = static::query()->withQueryCache($seconds);

        return $query;
    }


    /**
     * Вызывает макрос подключения лога query запроса
     *
     * @param bool|null $enabled
     * @return EloquentBuilder<static>
     */
    public static function withQueryLog(bool|null $enabled = null): EloquentBuilder
    {
        $query = static::query()->withQueryLog($enabled);

        return $query;
    }


    /**
     * Возвращает флаг о добавлении query запроса в кеш
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->isCached;
    }


    /**
     * Устанавливает флаг о добавлении query запроса в кеш
     *
     * @param bool $value
     * @return static
     */
    public function setCached(bool $value): static
    {
        $this->isCached = $value;

        return $this;
    }


    /**
     * Возвращает флаг о получении query запроса из кеша
     *
     * @return bool
     */
    public function isFromCached(): bool
    {
        return $this->isFromCache;
    }


    /**
     * Устанавливает флаг о получении query запроса из кеша
     *
     * @param bool $value
     * @return static
     */
    public function setFromCached(bool $value): static
    {
        $this->isFromCache = $value;

        return $this;
    }


    /**
     * Сбрасывает весь кеш модели
     *
     * @return static
     */
    public function flushCache(): static
    {
        app(QueryCacheService::class)->flushQueryCache($this);

        return $this;
    }
}
