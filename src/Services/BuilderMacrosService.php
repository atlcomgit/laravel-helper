<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Сервис регистрации builder макросов
 */
class BuilderMacrosService extends DefaultService
{
    /**
     * Добавляет макросы в конструкторы запросов
     *
     * @return void
     */
    public static function setMacros(): void
    {
        if (config('laravel-helper.query_cache.enabled')) {
            EloquentBuilder::macro('withQueryCache', function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            });

            EloquentBuilder::macro('withoutQueryCache', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryCache(false);
            });

            QueryBuilder::macro('withQueryCache', function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            });

            QueryBuilder::macro('withoutQueryCache', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryCache(false);
            });
        }

        if (config('laravel-helper.query_log.enabled')) {
            EloquentBuilder::macro('withQueryLog', function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            });

            EloquentBuilder::macro('withoutQueryLog', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryLog(false);
            });

            QueryBuilder::macro('withQueryLog', function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            });

            QueryBuilder::macro('withoutQueryLog', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryLog(false);
            });
        }

        if (config('laravel-helper.model_log.enabled')) {
            EloquentBuilder::macro('withModelLog', function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            });

            EloquentBuilder::macro('withoutModelLog', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withModelLog(false);
            });

            QueryBuilder::macro('withModelLog', function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            });

            QueryBuilder::macro('withoutModelLog', function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withModelLog(false);
            });
        }

        if (config('laravel-helper.macros.builder.enabled')) {
            // ...
        }
    }
}
