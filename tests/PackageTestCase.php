<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests;

use Atlcom\LaravelHelper\Traits\TestingTrait;
use Orchestra\Testbench\TestCase;

/**
 * Базовый класс для тестов пакета
 */
abstract class PackageTestCase extends TestCase
{
    use TestingTrait;
}
