<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\TelescopeTrait;

/**
 * Абстрактный класс для репозитория (singleton)
 */
abstract class DefaultRepository
{
    use TelescopeTrait;
}
