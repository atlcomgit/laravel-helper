<?php

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Listeners\HttpConnectionFailedListener;
use Atlcom\LaravelHelper\Listeners\HttpRequestSendingListener;
use Atlcom\LaravelHelper\Listeners\HttpResponseReceivedListener;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\HttpMacrosService;
use Atlcom\LaravelHelper\Services\StrMacrosService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

/**
 * Подключение пакета
 */
class LaravelHelperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Конфигурация
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-helper.php', 'laravel-helper');
        $this->publishes([__DIR__ . '/../../config/laravel-helper.php' => config_path('laravel-helper.php')]);

        // Миграции
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Регистрация обработчика исключений
        $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);
        // $this->renderable(fn (Throwable $e, $request) => app(DefaultExceptionHandler::class)->render($request, $e)));

        // Регистрация dto
        $this->app->resolving(Dto::class, function (Dto $dto, Application $app) {
            return $dto->fillFromRequest(request()->toArray());
        });

        // Регистрация сервисов
        // $this->app->bind(Service::class, Service::class);
    }


    public function boot(): void {
        // HttpLog events
        if (config('laravel-helper.http_log.out.enabled')) {
            Event::listen(RequestSending::class, HttpRequestSendingListener::class);
            Event::listen(ResponseReceived::class, HttpResponseReceivedListener::class);
            Event::listen(ConnectionFailed::class, HttpConnectionFailedListener::class);
        }

        // Строковые макросы
        if (config('laravel-helper.macros.str.enabled')) {
            StrMacrosService::setMacros();
        }

        // Http макросы
        if (config('laravel-helper.macros.http.enabled')) {
            HttpMacrosService::setMacros();
        }

        // Глобальные настройки запросов (laravel 10+)
        Http::globalOptions([
            'headers' => HttpLogService::getLogHeaders(HttpLogHeaderEnum::Unknown),
            'curl' => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ]);

    }
}
