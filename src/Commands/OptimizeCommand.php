<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Консольная команда optimize laravel-helper
 */
class OptimizeCommand extends DefaultCommand
{
    protected $signature = 'lh:optimize
        {--schedule= : Запуск команды по расписанию }
    ';
    protected $description = 'Оптимизация всех логов';
    protected $isolated = true;
    protected ?bool $withConsoleLog = true;
    protected ?bool $withTelegramLog = true;


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

        if (lhConfig(ConfigEnum::Optimize, 'log_cleanup.enabled')) {
            $this->telegramComment = ['Env' => config('app.env')];
            $this->withTelegramLog = isLocal() || isProd();

            $config = ConfigEnum::ConsoleLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->consoleLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $config = ConfigEnum::HttpLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->httpLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $config = ConfigEnum::ModelLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->modelLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $config = ConfigEnum::QueryLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->queryLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $config = ConfigEnum::QueueLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->queueLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $config = ConfigEnum::ViewLog;
            $cleanup = Schema::connection(LaravelHelperService::getConnection($config))
                ->hasTable(LaravelHelperService::getTable($config))
                ? $this->viewLogService->cleanup(
                    $isSchedule ? lhConfig($config, 'cleanup_days') : 0
                )
                : 0;
            $this->telegramComment[] = [
                $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
            ];

            $this->outputEol(json($this->telegramComment, JSON_PRETTY_PRINT), 'fg=green');
        }

        if (lhConfig(ConfigEnum::Optimize, 'cache_clear.enabled')) {
            if (lhConfig(ConfigEnum::QueryCache, 'enabled') || lhConfig(ConfigEnum::ViewCache, 'enabled')) {
                Cache::flush();
                $this->queryCacheService->flushQueryCacheAll();
            }
        }

        return self::SUCCESS;
    }
}
