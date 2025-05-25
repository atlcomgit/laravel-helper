<?php

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * Подключение обработчика ошибок
 */
class LaravelHelperExceptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);

        // $this->renderable(function (Throwable $e, $request) {
        //     return app(DefaultExceptionHandler::class)->render($request, $e));
        // });
    }


    public function boot(): void {}
}
