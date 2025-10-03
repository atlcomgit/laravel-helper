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
        $withQueryCacheEloquentBuilderMacro = function (int|string|bool|null $seconds = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                ? $this->withQueryCache($seconds)
                : $this;
        };
        EloquentBuilder::macro('withCache', $withQueryCacheEloquentBuilderMacro);
        EloquentBuilder::macro('withQueryCache', $withQueryCacheEloquentBuilderMacro);

        $withoutQueryCacheEloquentBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                ? $this->withQueryCache(false)
                : $this;
        };
        EloquentBuilder::macro('withoutCache', $withoutQueryCacheEloquentBuilderMacro);
        EloquentBuilder::macro('withoutQueryCache', $withoutQueryCacheEloquentBuilderMacro);

        $withQueryCacheQueryBuilderMacro = function (int|string|bool|null $seconds = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                ? $this->withQueryCache($seconds)
                : $this;
        };
        QueryBuilder::macro('withCache', $withQueryCacheQueryBuilderMacro);
        QueryBuilder::macro('withQueryCache', $withQueryCacheQueryBuilderMacro);

        $withoutQueryCacheQueryBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryCache, 'enabled')
                ? $this->withQueryCache(false)
                : $this;
        };
        QueryBuilder::macro('withoutCache', $withoutQueryCacheQueryBuilderMacro);
        QueryBuilder::macro('withoutQueryCache', $withoutQueryCacheQueryBuilderMacro);

        $withQueryLogEloquentBuilderMacro = function (bool|null $enabled = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryLog, 'enabled')
                ? $this->withQueryLog($enabled)
                : $this;
        };
        EloquentBuilder::macro('withLog', $withQueryLogEloquentBuilderMacro);
        EloquentBuilder::macro('withQueryLog', $withQueryLogEloquentBuilderMacro);

        $withoutQueryLogEloquentBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryLog, 'enabled')
                ? $this->withQueryLog(false)
                : $this;
        };
        EloquentBuilder::macro('withoutLog', $withoutQueryLogEloquentBuilderMacro);
        EloquentBuilder::macro('withoutQueryLog', $withoutQueryLogEloquentBuilderMacro);

        $withQueryLogQueryBuilderMacro = function (bool|null $enabled = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryLog, 'enabled')
                ? $this->withQueryLog($enabled)
                : $this;
        };
        QueryBuilder::macro('withLog', $withQueryLogQueryBuilderMacro);
        QueryBuilder::macro('withQueryLog', $withQueryLogQueryBuilderMacro);

        $withoutQueryLogQueryBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::QueryLog, 'enabled')
                ? $this->withQueryLog(false)
                : $this;
        };
        QueryBuilder::macro('withoutLog', $withoutQueryLogQueryBuilderMacro);
        QueryBuilder::macro('withoutQueryLog', $withoutQueryLogQueryBuilderMacro);

        $withModelLogEloquentBuilderMacro = function (bool|null $enabled = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::ModelLog, 'enabled')
                ? $this->withModelLog($enabled)
                : $this;
        };
        // EloquentBuilder::macro('withLog', $withModelLogEloquentBuilderMacro);
        EloquentBuilder::macro('withModelLog', $withModelLogEloquentBuilderMacro);

        $withoutModelLogEloquentBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::ModelLog, 'enabled')
                ? $this->withModelLog(false)
                : $this;
        };
        // EloquentBuilder::macro('withoutLog', $withoutModelLogEloquentBuilderMacro);
        EloquentBuilder::macro('withoutModelLog', $withoutModelLogEloquentBuilderMacro);

        $withModelLogQueryBuilderMacro = function (bool|null $enabled = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::ModelLog, 'enabled')
                ? $this->withModelLog($enabled)
                : $this;
        };
        // QueryBuilder::macro('withLog', $withModelLogQueryBuilderMacro);
        QueryBuilder::macro('withModelLog', $withModelLogQueryBuilderMacro);

        $withoutModelLogQueryBuilderMacro = function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return Lh::config(ConfigEnum::Macros, 'builder.enabled')
                && Lh::config(ConfigEnum::ModelLog, 'enabled')
                ? $this->withModelLog(false)
                : $this;
        };
        // QueryBuilder::macro('withoutLog', $withoutModelLogQueryBuilderMacro);
        QueryBuilder::macro('withoutModelLog', $withoutModelLogQueryBuilderMacro);

        if (Lh::config(ConfigEnum::Macros, 'builder.enabled')) {
            // ...
        }
    }
}
