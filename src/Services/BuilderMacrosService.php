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
            $withCacheEloquentBuilderMacro = function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            };
            EloquentBuilder::macro('withCache', $withCacheEloquentBuilderMacro);
            EloquentBuilder::macro('withQueryCache', $withCacheEloquentBuilderMacro);

            $withoutCacheEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryCache(false);
            };
            EloquentBuilder::macro('withoutCache', $withoutCacheEloquentBuilderMacro);
            EloquentBuilder::macro('withoutQueryCache', $withoutCacheEloquentBuilderMacro);

            $withCacheQueryBuilderMacro = function (int|string|bool|null $seconds = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var int|bool|null $seconds */
                return $this->withQueryCache($seconds);
            };
            QueryBuilder::macro('withCache', $withCacheQueryBuilderMacro);
            QueryBuilder::macro('withQueryCache', $withCacheQueryBuilderMacro);

            $withoutCacheQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryCache(false);
            };
            QueryBuilder::macro('withoutCache', $withoutCacheQueryBuilderMacro);
            QueryBuilder::macro('withoutQueryCache', $withoutCacheQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::QueryLog, 'enabled')) {
            $withLogEloquentBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            };
            EloquentBuilder::macro('withLog', $withLogEloquentBuilderMacro);
            EloquentBuilder::macro('withQueryLog', $withLogEloquentBuilderMacro);

            $withoutLogEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withQueryLog(false);
            };
            EloquentBuilder::macro('withoutLog', $withoutLogEloquentBuilderMacro);
            EloquentBuilder::macro('withoutQueryLog', $withoutLogEloquentBuilderMacro);

            $withLogQueryBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withQueryLog($enabled);
            };
            QueryBuilder::macro('withLog', $withLogQueryBuilderMacro);
            QueryBuilder::macro('withQueryLog', $withLogQueryBuilderMacro);

            $withoutLogQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withQueryLog(false);
            };
            QueryBuilder::macro('withoutLog', $withoutLogQueryBuilderMacro);
            QueryBuilder::macro('withoutQueryLog', $withoutLogQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::ModelLog, 'enabled')) {
            $withLogEloquentBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            };
            EloquentBuilder::macro('withLog', $withLogEloquentBuilderMacro);
            EloquentBuilder::macro('withModelLog', $withLogEloquentBuilderMacro);

            $withoutLogEloquentBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
                return $this->withModelLog(false);
            };
            EloquentBuilder::macro('withoutLog', $withoutLogEloquentBuilderMacro);
            EloquentBuilder::macro('withoutModelLog', $withoutLogEloquentBuilderMacro);

            $withLogQueryBuilderMacro = function (bool|null $enabled = null) {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                /** @var bool|null $enabled */
                return $this->withModelLog($enabled);
            };
            QueryBuilder::macro('withLog', $withLogQueryBuilderMacro);
            QueryBuilder::macro('withModelLog', $withLogQueryBuilderMacro);

            $withoutLogQueryBuilderMacro = function () {
                /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
                return $this->withModelLog(false);
            };
            QueryBuilder::macro('withoutLog', $withoutLogQueryBuilderMacro);
            QueryBuilder::macro('withoutModelLog', $withoutLogQueryBuilderMacro);
        }

        if (Lh::config(ConfigEnum::Macros, 'builder.enabled')) {
            // ...
        }
    }
}
