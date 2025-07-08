<?php

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Commands\CacheClearCommand;
use Atlcom\LaravelHelper\Commands\OptimizeCommand;
use Atlcom\LaravelHelper\Commands\ConsoleLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\HttpLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\ModelLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\OptimizeOverrideCommand;
use Atlcom\LaravelHelper\Commands\QueryLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\QueueLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\RouteCacheCommand;
use Atlcom\LaravelHelper\Commands\RouteLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\ViewLogCleanupCommand;
use Atlcom\LaravelHelper\Databases\Connections\ConnectionFactory;
use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Listeners\HttpConnectionFailedListener;
use Atlcom\LaravelHelper\Listeners\HttpRequestSendingListener;
use Atlcom\LaravelHelper\Listeners\HttpResponseReceivedListener;
use Atlcom\LaravelHelper\Listeners\TestFinishedListener;
use Atlcom\LaravelHelper\Middlewares\HttpLogMiddleware;
use Atlcom\LaravelHelper\Middlewares\RouteLogMiddleware;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
use Atlcom\LaravelHelper\Services\BuilderMacrosService;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\HttpMacrosService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Atlcom\LaravelHelper\Services\StrMacrosService;
use Atlcom\LaravelHelper\Services\TelegramApiService;
use Atlcom\LaravelHelper\Services\TelegramService;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Console\ConfigCacheCommand;
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
        // Регистрация настроек пакета
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-helper.php', 'laravel-helper');

        // Публикация настроек пакета
        $this->publishes(
            [__DIR__ . '/../../config/laravel-helper.php' => config_path('laravel-helper.php')],
            'laravel-helper',
        );

        // Регистрация миграций
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Публикация миграций
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'laravel-helper');

        // Регистрация фабрик
        $this->loadFactoriesFrom(__DIR__ . '/../../database/factories');

        // Регистрация обработчика исключений
        $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);
        // $this->renderable(fn (Throwable $e, $request) => app(DefaultExceptionHandler::class)->render($request, $e)));

        // Регистрация dto
        $this->app->resolving(
            Dto::class,
            fn (Dto $dto, Application $app) => $dto->fillFromRequest(request()->toArray())
        );

        // Регистрация сервисов
        $this->app->singleton(LaravelHelperService::class);
        $this->app->singleton(ModelLogService::class);
        $this->app->singleton(ConsoleLogService::class);
        $this->app->singleton(HttpLogService::class);
        $this->app->singleton(QueueLogService::class);
        $this->app->singleton(RouteLogService::class);
        $this->app->singleton(TelegramApiService::class);
        $this->app->singleton(TelegramService::class);
        $this->app->singleton(QueryCacheService::class);
        $this->app->singleton(QueryLogService::class);
        $this->app->singleton(ViewCacheService::class);
        $this->app->singleton(ViewLogService::class);
        $this->app->singleton(ModelLogObserver::class);

        // $this->app->singleton('db.factory', fn ($app) => new ConnectionFactory($app)); not need
        $this->app->bind('db.factory', fn ($app) => new ConnectionFactory($app));
    }


    public function boot(): void
    {
        // Проверка параметров конфига laravel-helper
        app(LaravelHelperService::class)->checkConfig();

        // Подключение событий HttpLog
        if (lhConfig(ConfigEnum::HttpLog, 'enabled') && lhConfig(ConfigEnum::HttpLog, 'out.enabled')) {
            Event::listen(RequestSending::class, HttpRequestSendingListener::class);
            Event::listen(ResponseReceived::class, HttpResponseReceivedListener::class);
            Event::listen(ConnectionFailed::class, HttpConnectionFailedListener::class);
        }

        // Подключение макросов Builder
        !(lhConfig(ConfigEnum::Macros, 'builder.enabled') || lhConfig(ConfigEnum::QueryCache, 'enabled'))
            ?: BuilderMacrosService::setMacros();
        // Подключение макросов Str
        !lhConfig(ConfigEnum::Macros, 'str.enabled') ?: StrMacrosService::setMacros();
        // Подключение макросов Http
        !lhConfig(ConfigEnum::Macros, 'http.enabled') ?: HttpMacrosService::setMacros();

        // Глобальные настройки запросов (laravel 10+)
        !lhConfig(ConfigEnum::HttpLog, 'out.global') ?: Http::globalOptions([
            'headers' => HttpLogService::getLogHeaders(HttpLogHeaderEnum::Unknown),
            'curl' => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ]);

        // Подключение консольных команд
        if ($this->app->runningInConsole()) {
            $this->commands([
                OptimizeCommand::class,
                CacheClearCommand::class,
                RouteCacheCommand::class,

                ConsoleLogCleanupCommand::class,
                HttpLogCleanupCommand::class,
                ModelLogCleanupCommand::class,
                RouteLogCleanupCommand::class,
                QueueLogCleanupCommand::class,
                QueryLogCleanupCommand::class,
                ViewLogCleanupCommand::class,
            ]);

            // Запуск команд по расписанию
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(OptimizeCommand::class, ['--telegram' => true, '--schedule' => true])
                    ->dailyAt('03:00');
            });

            // Запуск команд при выполнении artisan optimize
            method_exists($this, 'optimizes')
                ? $this->optimizes(
                    optimize: OptimizeCommand::class,
                    clear: CacheClearCommand::class,
                )
                : $this->commands([
                    OptimizeOverrideCommand::class,
                ]);
        }

        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        // Подключение middleware глобально
        $kernel->prependMiddlewareToGroup('web', RouteLogMiddleware::class);
        $kernel->prependMiddlewareToGroup('api', RouteLogMiddleware::class);
        if (lhConfig(ConfigEnum::HttpLog, 'in.global')) {
            $kernel->prependMiddlewareToGroup('web', HttpLogMiddleware::class);
            $kernel->prependMiddlewareToGroup('api', HttpLogMiddleware::class);
        }

        // Подключение логирования очередей
        Queue::before(fn (JobProcessing $event) => app(QueueLogService::class)->job($event));
        Queue::after(fn (JobProcessed $event) => app(QueueLogService::class)->job($event));
        Queue::failing(fn (JobFailed $event) => app(QueueLogService::class)->job($event));

        // not need
        // Event::listen(CommandStarting::class, function (CommandStarting $event) {
        //     match ($event->command) {
        //         ConfigCacheCommand::class => '',
        //         // 'config:cache'
        //     };
        //     if (in_array($event->command, ['config:cache', 'route:cache', 'view:cache'])) {
        //         // Выполнить действия
        //         logger()->info("Выполняется {$event->command} — действия пакета.");
        //     }
        // });
    }
}
