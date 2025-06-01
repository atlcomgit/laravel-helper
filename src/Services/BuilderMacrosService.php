<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Сервис регистрации builder макросов
 */
class BuilderMacrosService
{
    /**
     * Добавляет макросы в конструкторы запросов
     *
     * @return void
     */
    public static function setMacros(): void
    {
        EloquentBuilder::macro('withCache', function (int|bool|null $seconds = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            /** @var int|null $seconds */
            return $this->setUseWithCache($seconds);
        });

        EloquentBuilder::macro('withoutCache', function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder $this */
            return $this->setUseWithCache(false);
        });

        QueryBuilder::macro('withCache', function (int|bool|null $seconds = null) {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            /** @var int|null $seconds */
            return $this->setUseWithCache($seconds);
        });

        QueryBuilder::macro('withoutCache', function () {
            /** @var \Atlcom\LaravelHelper\Databases\Builders\QueryBuilder $this */
            return $this->setUseWithCache(false);
        });
    }
}
