<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\TelescopeTrait;

/**
 * Абстрактный класс для сервиса (singleton)
 */
abstract class DefaultService
{
    use TelescopeTrait;
}
