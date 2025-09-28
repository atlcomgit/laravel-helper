<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @internal
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
        if (Lh::config(ConfigEnum::QueryCache, 'enabled')) {
            $withQueryCacheEloquentBuilderMacro = function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            };
            EloquentBuilder::macro('withCache', $withQueryCacheEloquentBuilderMacro);
            EloquentBuilder::macro('withQueryCache', $withQueryCacheEloquentBuilderMacro);

            $withoutQueryCacheEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryCache(false);
            };
            EloquentBuilder::macro('withoutCache', $withoutQueryCacheEloquentBuilderMacro);
            EloquentBuilder::macro('withoutQueryCache', $withoutQueryCacheEloquentBuilderMacro);

            $withQueryCacheQueryBuilderMacro = function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            };
            QueryBuilder::macro('withCache', $withQueryCacheQueryBuilderMacro);
            QueryBuilder::macro('withQueryCache', $withQueryCacheQueryBuilderMacro);

            $withoutQueryCacheQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryCache(false);
            };
            QueryBuilder::macro('withoutCache', $withoutQueryCacheQueryBuilderMacro);
            QueryBuilder::macro('withoutQueryCache', $withoutQueryCacheQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::QueryLog, 'enabled')) {
            $withQueryLogEloquentBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            };
            EloquentBuilder::macro('withLog', $withQueryLogEloquentBuilderMacro);
            EloquentBuilder::macro('withQueryLog', $withQueryLogEloquentBuilderMacro);

            $withoutQueryLogEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryLog(false);
            };
            EloquentBuilder::macro('withoutLog', $withoutQueryLogEloquentBuilderMacro);
            EloquentBuilder::macro('withoutQueryLog', $withoutQueryLogEloquentBuilderMacro);

            $withQueryLogQueryBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            };
            QueryBuilder::macro('withLog', $withQueryLogQueryBuilderMacro);
            QueryBuilder::macro('withQueryLog', $withQueryLogQueryBuilderMacro);

            $withoutQueryLogQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryLog(false);
            };
            QueryBuilder::macro('withoutLog', $withoutQueryLogQueryBuilderMacro);
            QueryBuilder::macro('withoutQueryLog', $withoutQueryLogQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::ModelLog, 'enabled')) {
            $withModelLogEloquentBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            };
            EloquentBuilder::macro('withLog', $withModelLogEloquentBuilderMacro);
            EloquentBuilder::macro('withModelLog', $withModelLogEloquentBuilderMacro);

            $withoutModelLogEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withModelLog(false);
            };
            EloquentBuilder::macro('withoutLog', $withoutModelLogEloquentBuilderMacro);
            EloquentBuilder::macro('withoutModelLog', $withoutModelLogEloquentBuilderMacro);

            $withModelLogQueryBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            };
            QueryBuilder::macro('withLog', $withModelLogQueryBuilderMacro);
            QueryBuilder::macro('withModelLog', $withModelLogQueryBuilderMacro);

            $withoutModelLogQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withModelLog(false);
            };
            QueryBuilder::macro('withoutLog', $withoutModelLogQueryBuilderMacro);
            QueryBuilder::macro('withoutModelLog', $withoutModelLogQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::Macros, 'builder.enabled')) {
            // ...
        }
    }
}
