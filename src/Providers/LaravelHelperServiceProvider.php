<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Commands\CacheClearCommand;
use Atlcom\LaravelHelper\Commands\OptimizeCommand;
use Atlcom\LaravelHelper\Commands\ConsoleLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\HttpLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\IpBlockCleanupCommand;
use Atlcom\LaravelHelper\Commands\MailLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\ModelLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\OptimizeOverrideCommand;
use Atlcom\LaravelHelper\Commands\ProfilerLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\QueryLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\QueueLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\RouteCacheCommand;
use Atlcom\LaravelHelper\Commands\RouteLogCleanupCommand;
use Atlcom\LaravelHelper\Commands\SwaggerCommand;
use Atlcom\LaravelHelper\Commands\TelegramBotCommand;
use Atlcom\LaravelHelper\Commands\ViewLogCleanupCommand;
use Atlcom\LaravelHelper\Databases\Connections\ConnectionFactory;
use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Events\MailFailed;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Listeners\HttpConnectionFailedListener;
use Atlcom\LaravelHelper\Listeners\HttpRequestSendingListener;
use Atlcom\LaravelHelper\Listeners\HttpResponseReceivedListener;
use Atlcom\LaravelHelper\Listeners\MailMessageFailedListener;
use Atlcom\LaravelHelper\Listeners\MailMessageSendingListener;
use Atlcom\LaravelHelper\Listeners\MailMessageSentListener;
use Atlcom\LaravelHelper\Listeners\TelegramBotEventListener;
use Atlcom\LaravelHelper\Middlewares\HttpCacheMiddleware;
use Atlcom\LaravelHelper\Middlewares\HttpLogMiddleware;
use Atlcom\LaravelHelper\Middlewares\IpBlockMiddleware;
use Atlcom\LaravelHelper\Middlewares\RouteLogMiddleware;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
use Atlcom\LaravelHelper\Services\BuilderMacrosService;
use Atlcom\LaravelHelper\Services\CacheService;
use Atlcom\LaravelHelper\Services\CollectionMacrosService;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpCacheService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\IpBlockService;
use Atlcom\LaravelHelper\Services\HttpMacrosService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\MailLogService;
use Atlcom\LaravelHelper\Services\MailMacrosService;
use Atlcom\LaravelHelper\Services\MigrationService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\ProfilerLogService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Atlcom\LaravelHelper\Services\SingletonService;
use Atlcom\LaravelHelper\Services\StrMacrosService;
use Atlcom\LaravelHelper\Services\TelegramApiService;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotChatService;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotListenerService;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotUserService;
use Atlcom\LaravelHelper\Services\TelegramLogService;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
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
        $publishConfigs = [
            __DIR__ . '/../../config/laravel-helper.php' => config_path('laravel-helper.php'),
        ];

        if (Lh::config(ConfigEnum::IpBlock, 'enabled')) {
            $publishConfigs[__DIR__ . '/../../config/laravel-helper-ip-block-patterns.php']
                = config_path('laravel-helper-ip-block-patterns.php');
        }

        $this->publishes($publishConfigs, 'laravel-helper');

        // Регистрация миграций
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        !Lh::config(ConfigEnum::TelegramBot, 'enabled')
            ?: $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations_telegram_bot');

        // Публикация миграций
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'laravel-helper');

        // Регистрация фабрик
        $this->loadFactoriesFrom(__DIR__ . '/../../database/factories');

        // Регистрация роутов
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api-telegram-bot.php');

        // Регистрация сервиса миграций
        $this->app->singleton(MigrationService::class);
        app(MigrationService::class)->disableQueryCacheDuringMigrations();

        // Регистрация обработчика исключений
        $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);
        // $this->renderable(fn (Throwable $e, $request) => app(DefaultExceptionHandler::class)->render($request, $e)));

        // Регистрация dto
        $this->app->resolving(
            Dto::class,
            fn (Dto $dto, Application $app) => $dto->fillFromRequest(request()->toArray()) //?!? проверка массива на запрещенные слова HELPER_DTO_FORBIDDEN_WORDS_ENABLED
        );

        // Регистрация сервисов
        $this->app->singleton(LaravelHelperService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(ConsoleLogService::class);
        $this->app->singleton(HttpCacheService::class);
        $this->app->singleton(HttpLogService::class);
        $this->app->singleton(IpBlockService::class);
        $this->app->singleton(MailLogService::class);
        $this->app->singleton(ModelLogService::class);
        ;
        $this->app->singleton(HttpLogService::class);
        $this->app->singleton(ModelLogService::class);
        $this->app->singleton(ProfilerLogService::class);
        $this->app->singleton(RouteLogService::class);
        $this->app->singleton(QueueLogService::class);
        $this->app->singleton(QueryCacheService::class);
        $this->app->singleton(QueryLogService::class);
        $this->app->singleton(TelegramApiService::class);
        $this->app->singleton(TelegramBotChatService::class);
        $this->app->singleton(TelegramBotUserService::class);
        $this->app->singleton(TelegramBotMessageService::class);
        $this->app->singleton(TelegramBotListenerService::class);
        $this->app->singleton(TelegramLogService::class);
        $this->app->singleton(TelegramBotService::class);
        $this->app->singleton(ViewCacheService::class);
        $this->app->singleton(ViewLogService::class);

        $this->app->singleton(ModelLogObserver::class);

        // Переопределение менеджера почты для поддержки логирования
        if (Lh::config(ConfigEnum::MailLog, 'enabled')) {
            $this->app->extend('mail.manager', function ($service, $app) {
                return new \Atlcom\LaravelHelper\Mail\HelperMailManager($app);
            });
        }

        // Регистрация обработчика соединений
        if (
            Lh::config(ConfigEnum::QueryCache, 'enabled')
            || Lh::config(ConfigEnum::QueryLog, 'enabled')
            || Lh::config(ConfigEnum::ModelLog, 'enabled')
        ) {
            // $this->app->singleton('db.factory', fn ($app) => new ConnectionFactory($app)); not need
            $this->app->bind('db.factory', fn ($app) => new ConnectionFactory($app));
        }

        SingletonService::register();
    }


    public function boot(): void
    {
        // Проверка параметров конфига laravel-helper
        Lh::checkConfig();

        // Подключение событий HttpLog
        if (Lh::config(ConfigEnum::HttpLog, 'enabled') && Lh::config(ConfigEnum::HttpLog, 'out.enabled')) {
            Event::listen(RequestSending::class, HttpRequestSendingListener::class);
            Event::listen(ResponseReceived::class, HttpResponseReceivedListener::class);
            Event::listen(ConnectionFailed::class, HttpConnectionFailedListener::class);
        }

        // Подключение событий MailLog
        if (Lh::config(ConfigEnum::MailLog, 'enabled')) {
            Event::listen(MessageSending::class, MailMessageSendingListener::class);
            Event::listen(MessageSent::class, MailMessageSentListener::class);
            Event::listen(MailFailed::class, MailMessageFailedListener::class);
        }

        // Подключение событий телеграм бота
        !Lh::config(ConfigEnum::TelegramBot, 'enabled')
            ?: Event::listen(TelegramBotEvent::class, TelegramBotEventListener::class);

        // Подключение макросов Builder
        BuilderMacrosService::setMacros();
        // Подключение макросов Str
        StrMacrosService::setMacros();
        // Подключение макросов Collection
        CollectionMacrosService::setMacros();
        // Подключение макросов Http
        HttpMacrosService::setMacros();
        // Подключение макросов Mail
        MailMacrosService::setMacros();

        // Глобальные настройки запросов (laravel 10+)
        !Lh::config(ConfigEnum::HttpLog, 'out.global') ?: Http::globalOptions([
            'headers' => HttpLogService::getLogHeaders(HttpLogHeaderEnum::Unknown),
            'curl'    => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ]);

        // Подключение консольных команд
        if ($this->app->runningInConsole()) {
            $this->commands([
                OptimizeCommand::class,
                CacheClearCommand::class,
                RouteCacheCommand::class,
                SwaggerCommand::class,

                ConsoleLogCleanupCommand::class,
                HttpLogCleanupCommand::class,
                IpBlockCleanupCommand::class,
                MailLogCleanupCommand::class,
                ModelLogCleanupCommand::class,
                ProfilerLogCleanupCommand::class,
                RouteLogCleanupCommand::class,
                QueueLogCleanupCommand::class,
                QueryLogCleanupCommand::class,
                ViewLogCleanupCommand::class,

                TelegramBotCommand::class,
            ]);

            // Запуск команд по расписанию
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(OptimizeCommand::class, ['--telegram', '--schedule'])->dailyAt('03:00');
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

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        // Подключение middleware глобально
        if (Lh::config(ConfigEnum::IpBlock, 'enabled')) {
            $kernel->prependMiddlewareToGroup('web', IpBlockMiddleware::class);
            $kernel->prependMiddlewareToGroup('api', IpBlockMiddleware::class);
        }

        $kernel->prependMiddlewareToGroup('web', RouteLogMiddleware::class);
        $kernel->prependMiddlewareToGroup('api', RouteLogMiddleware::class);

        if (Lh::config(ConfigEnum::HttpCache, 'global')) {
            $kernel->prependMiddlewareToGroup('web', HttpCacheMiddleware::class);
            $kernel->prependMiddlewareToGroup('api', HttpCacheMiddleware::class);
        }
        if (Lh::config(ConfigEnum::HttpLog, 'in.global')) {
            $kernel->prependMiddlewareToGroup('web', HttpLogMiddleware::class);
            $kernel->prependMiddlewareToGroup('api', HttpLogMiddleware::class);
        }

        // Регистрация alias
        Route::aliasMiddleware('withIpBlock', IpBlockMiddleware::class);
        Route::aliasMiddleware('withHttpCache', HttpCacheMiddleware::class);
        Route::aliasMiddleware('withHttpLog', HttpLogMiddleware::class);

        // Подключение логирования очередей
        if (Lh::config(ConfigEnum::QueueLog, 'enabled')) {
            Queue::before(fn (JobProcessing $event) => app(QueueLogService::class)->job($event));
            Queue::after(fn (JobProcessed $event) => app(QueueLogService::class)->job($event));
            Queue::failing(fn (JobFailed $event) => app(QueueLogService::class)->job($event));
        }

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
