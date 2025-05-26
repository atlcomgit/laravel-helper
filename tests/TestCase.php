<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests;

use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Подключает провайдеры к тестам
     *
     * @param mixed $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }
}