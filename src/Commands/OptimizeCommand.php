<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpCacheService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\ProfilerLogService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\SingletonService;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Support\Facades\Schema;

/**
 * Консольная команда optimize laravel-helper
 */
class OptimizeCommand extends DefaultCommand
{
    protected $signature = 'lh:optimize
        {--schedule : Запуск команды по расписанию }
    ';
    protected $description = 'Оптимизация всех логов';
    protected $isolated = true;
    protected ?bool $withConsoleLog = true;
    protected ?bool $withTelegramLog = true;


    public function __construct(
        protected ConsoleLogService $consoleLogService,
        protected HttpCacheService $httpCacheService,
        protected HttpLogService $httpLogService,
        protected ModelLogService $modelLogService,
        protected ProfilerLogService $profileLogService,
        protected QueryCacheService $queryCacheService,
        protected QueryLogService $queryLogService,
        protected QueueLogService $queueLogService,
        protected ViewCacheService $viewCacheService,
        protected ViewLogService $viewLogService,
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

        // Очистка таблиц логов
        if (Lh::config(ConfigEnum::Optimize, 'log_cleanup.enabled')) {
            $this->telegramComment = ['Env' => config('app.env')];
            $this->withTelegramLog = isLocal() || isProd();

            foreach ([
                ['config' => ConfigEnum::ConsoleLog, 'service' => $this->consoleLogService],
                ['config' => ConfigEnum::HttpLog, 'service' => $this->httpLogService],
                ['config' => ConfigEnum::ModelLog, 'service' => $this->modelLogService],
                ['config' => ConfigEnum::ProfilerLog, 'service' => $this->profileLogService],
                ['config' => ConfigEnum::QueryLog, 'service' => $this->queryLogService],
                ['config' => ConfigEnum::QueueLog, 'service' => $this->queueLogService],
                ['config' => ConfigEnum::ViewLog, 'service' => $this->viewLogService],
            ] as $log) {
                $config = $log['config'];
                $service = $log['service'];
                $cleanup = Schema::connection(Lh::getConnection($config))
                    ->hasTable(Lh::getTable($config))
                    ? $service->cleanup($isSchedule ? Lh::config($config, 'cleanup_days') : 0)
                    : 0;
                $this->telegramComment = [
                    ...$this->telegramComment,
                    $config->value => 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']),
                ];
            }


            $this->outputEol(json($this->telegramComment, JSON_PRETTY_PRINT), 'fg=green');
        }

        // Очистка кеша
        if (Lh::config(ConfigEnum::Optimize, 'cache_clear.enabled')) {
            if (
                Lh::config(ConfigEnum::HttpCache, 'enabled')
                || Lh::config(ConfigEnum::QueryCache, 'enabled')
                || Lh::config(ConfigEnum::ViewCache, 'enabled')
            ) {
                $this->httpCacheService->flushHttpCacheAll();
                $this->queryCacheService->flushQueryCacheAll();
                $this->viewCacheService->flushViewCacheAll();
            }
        }

        // Запуск кеширования классов singleton
        if (!$isSchedule && ($singletons = SingletonService::optimize())) {
            foreach ($singletons as $singleton) {
                $this->outputEol($singleton, 'fg=green');
            }
        }

        return self::SUCCESS;
    }
}
