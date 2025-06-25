<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\TestingTrait;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Абстрактный класс для тестов
 */
abstract class DefaultTest extends TestCase
{
    use TestingTrait;
}
