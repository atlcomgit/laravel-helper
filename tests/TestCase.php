<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests;

use Atlcom\LaravelHelper\Providers\LaravelHelperDtoServiceProvider;
use Atlcom\LaravelHelper\Providers\LaravelHelperExceptionServiceProvider;
use Atlcom\LaravelHelper\Providers\LaravelHelperMacroServiceProvider;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app) {
        return [
            LaravelHelperServiceProvider::class,
            LaravelHelperMacroServiceProvider::class,
            LaravelHelperDtoServiceProvider::class,
            LaravelHelperExceptionServiceProvider::class,
        ];
    }
}