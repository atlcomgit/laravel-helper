<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\ProfilerLogTrait;

/**
 * Абстрактный класс для логов профилирования методов класса
 */
abstract class DefaultProfiler
{
    use ProfilerLogTrait;
}
