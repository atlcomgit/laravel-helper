<?php

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Commands\ConsoleLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\HttpLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\ModelLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\QueueLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\RouteLogCleanupCommand;
use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Listeners\HttpConnectionFailedListener;
use Atlcom\LaravelHelper\Listeners\HttpRequestSendingListener;
use Atlcom\LaravelHelper\Listeners\HttpResponseReceivedListener;
use Atlcom\LaravelHelper\Middlewares\HttpLogMiddleware;
use Atlcom\LaravelHelper\Middlewares\RouteLogMiddleware;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\HttpMacrosService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\StrMacrosService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

/**
 * Сервис провайдер для подключения пакета laravel-helper
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

        // Фабрики
        $this->loadFactoriesFrom(__DIR__ . '/../../database/factories');

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


    public function boot(): void
    {
        // Проверка параметров конфига laravel-helper
        app(LaravelHelperService::class)->checkConfig();

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

        // Регистрация консольных команд
        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsoleLogCleanupCommand::class,
                HttpLogCleanupCommand::class,
                ModelLogCleanupCommand::class,
                RouteLogCleanupCommand::class,
                QueueLogCleanupCommand::class,
            ]);

            // Запуск команд по расписанию
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);

                // Очистка console_logs
                $schedule->command(ConsoleLogCleanupCommand::class, ['--telegram'])->dailyAt('03:01');
                // Очистка http_logs
                $schedule->command(HttpLogCleanupCommand::class, ['--telegram'])->dailyAt('03:02');
                // Очистка model_logs
                $schedule->command(ModelLogCleanupCommand::class, ['--telegram'])->dailyAt('03:03');
                // Очистка route_logs
                $schedule->command(RouteLogCleanupCommand::class, ['--telegram'])->dailyAt('03:04');
                // Очистка queue_logs
                $schedule->command(QueueLogCleanupCommand::class, ['--telegram'])->dailyAt('03:05');
            });
        }


        // Добавить middleware глобально
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->prependMiddleware(HttpLogMiddleware::class);
        $kernel->prependMiddleware(RouteLogMiddleware::class);

        // Логирование задач
        Queue::before(fn (JobProcessing $event) => app(QueueLogService::class)->job($event));
        Queue::after(fn (JobProcessed $event) => app(QueueLogService::class)->job($event));
        Queue::failing(fn (JobFailed $event) => app(QueueLogService::class)->job($event));
    }
}
