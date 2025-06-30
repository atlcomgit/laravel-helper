<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

/**
 * Трейт для работы с telescope
 */
trait TelescopeTrait
{
    /**
     * Выполняет обращение к базе данных с отключением telescope
     *
     * @param callable $callable
     * @return mixed
     */
    public function withoutTelescope(callable $callable): mixed
    {
        $telescope = telescope();
        telescope(false);

        $result = $callable();

        telescope($telescope);

        return $result;
    }
}
