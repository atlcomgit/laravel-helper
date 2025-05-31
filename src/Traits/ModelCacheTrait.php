<?php

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Observers\QueryCacheObserver;

/**
 * Трейт для подключения кеширования модели
 * 
 * @property bool $logEnabled
 * @property array $logExcludeAttributes
 * @property array $logHideAttributes
 * 
 * @method mixed withCache(?int $seconds = null)
 */
trait ModelCacheTrait
{
    /**
     * Автозагрузка трейта
     *
     * @return void
     */
    protected static function bootModelCacheTrait()
    {
        if (config('laravel-helper.query_cache.enabled')) {
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
        return new EloquentBuilder($query);
    }
}
