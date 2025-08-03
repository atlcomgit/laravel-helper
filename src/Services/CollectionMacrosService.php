<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Illuminate\Support\Collection;

/**
 * Сервис регистрации Collection макросов
 */
class CollectionMacrosService extends DefaultService
{
    /**
     * Добавляет макросы в коллекции
     *
     * @return void
     */
    public static function setMacros(): void
    {
        !method_exists(Hlp::class, 'objectToArrayRecursive')
            ?: Collection::macro(
                'toArrayRecursive',
                fn () => /** @var Collection $this */ Hlp::objectToArrayRecursive($this)
            );
    }
}
