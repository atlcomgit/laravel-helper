<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\TestCaseTrait;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Абстрактный класс для тестов
 */
abstract class DefaultTest extends TestCase
{
    use TestCaseTrait;
}
