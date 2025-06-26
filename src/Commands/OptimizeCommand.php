<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Консольная команда очистки логов консольных команд
 */
class OptimizeCommand extends DefaultCommand
{
    protected $signature = 'lh:optimize
        {--schedule= : Запуск команды по расписанию }
    ';
    protected $description = 'Оптимизация всех логов';
    protected $isolated = true;
    protected bool $withConsoleLog = true;


    public function __construct(
        protected ConsoleLogService $consoleLogService,
        protected HttpLogService $httpLogService,
        protected ModelLogService $modelLogService,
        protected QueryLogService $queryLogService,
        protected QueueLogService $queueLogService,
        protected RouteLogService $routeLogService,
        protected ViewLogService $viewLogService,
        protected QueryCacheService $queryCacheService,
    ) {
        parent::__construct();
    }


    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        $this->outputBold($this->description);
        $this->outputEol();

        $isSchedule = $this->hasOption('schedule') && Hlp::castToBool($this->option('schedule'));

        if (config('laravel-helper.optimize.log_cleanup.enabled')) {
            $cleanupConsoleLog = Schema::connection(config('laravel-helper.console_log.connection'))
                ->hasTable(config('laravel-helper.console_log.table'))
                ? $this->consoleLogService->cleanup(
                    $isSchedule ? config('laravel-helper.console_log.cleanup_days') : 0
                )
                : 0;
            $cleanupHttpLog = Schema::connection(config('laravel-helper.http_log.connection'))
                ->hasTable(config('laravel-helper.http_log.table'))
                ? $this->httpLogService->cleanup(
                    $isSchedule ? config('laravel-helper.http_log.cleanup_days') : 0
                )
                : 0;
            $cleanupModelLog = Schema::connection(config('laravel-helper.model_log.connection'))
                ->hasTable(config('laravel-helper.model_log.table'))
                ? $this->modelLogService->cleanup(
                    $isSchedule ? config('laravel-helper.model_log.cleanup_days') : 0
                )
                : 0;
            $cleanupQueryLog = Schema::connection(config('laravel-helper.query_log.connection'))
                ->hasTable(config('laravel-helper.query_log.table'))
                ? $this->queryLogService->cleanup(
                    $isSchedule ? config('laravel-helper.query_log.cleanup_days') : 0
                )
                : 0;
            $cleanupQueueLog = Schema::connection(config('laravel-helper.queue_log.connection'))
                ->hasTable(config('laravel-helper.queue_log.table'))
                ? $this->queueLogService->cleanup(
                    $isSchedule ? config('laravel-helper.queue_log.cleanup_days') : 0
                )
                : 0;
            $cleanupRouteLog = Schema::connection(config('laravel-helper.route_log.connection'))
                ->hasTable(config('laravel-helper.route_log.table'))
                ? $this->routeLogService->cleanup()
                : 0;
            $cleanupViewLog = Schema::connection(config('laravel-helper.view_log.connection'))
                ->hasTable(config('laravel-helper.view_log.table'))
                ? $this->viewLogService->cleanup(
                    $isSchedule ? config('laravel-helper.view_log.cleanup_days') : 0
                )
                : 0;

            $this->withTelegramLog = (isLocal() || isProd())
                && (
                    $cleanupConsoleLog > 0
                    || $cleanupHttpLog > 0
                    || $cleanupModelLog > 0
                    || $cleanupQueryLog > 0
                    || $cleanupQueueLog > 0
                    || $cleanupRouteLog > 0
                    || $cleanupViewLog > 0
                );
            $this->telegramComment = [
                'ConsoleLog' => Hlp::stringPlural($cleanupConsoleLog, ['записей', 'запись', 'записи']),
                'HttpLog' => Hlp::stringPlural($cleanupHttpLog, ['записей', 'запись', 'записи']),
                'ModelLog' => Hlp::stringPlural($cleanupModelLog, ['записей', 'запись', 'записи']),
                'QueryLog' => Hlp::stringPlural($cleanupQueryLog, ['записей', 'запись', 'записи']),
                'QueueLog' => Hlp::stringPlural($cleanupQueueLog, ['записей', 'запись', 'записи']),
                'RouteLog' => Hlp::stringPlural($cleanupRouteLog, ['записей', 'запись', 'записи']),
                'ViewLog' => Hlp::stringPlural($cleanupViewLog, ['записей', 'запись', 'записи']),
            ];

            $this->outputEol($this->telegramComment, 'fg=green');
        }

        if (config('laravel-helper.optimize.cache_clear.enabled')) {
            if (config('laravel-helper.query_cache.enabled') || config('laravel-helper.view_cache.enabled')) {
                Cache::flush();
                $this->queryCacheService->flushQueryCacheAll();
            }
        }

        return self::SUCCESS;
    }
}
