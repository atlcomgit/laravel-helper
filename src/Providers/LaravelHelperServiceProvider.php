<?php

namespace Atlcom\LaravelHelper\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Подключение пакета
 */
class LaravelHelperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-helper.php', 'laravel-helper');

        // $this->app->bind(Service::class, Service::class);
    }


    public function boot(): void {}
}
