<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\ModelLogService;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Atlcom\LaravelHelper\Services\QueueLogService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Atlcom\LaravelHelper\Services\ViewLogService;

/**
 * Консольная команда очистки логов консольных команд
 */
class AllCleanupCommand extends DefaultCommand
{
    protected $signature = 'cleanup:all';
    protected $description = 'Очистка всех логов';
    protected $isolated = true;
    protected bool $logEnabled = true;


    public function __construct(
        protected ConsoleLogService $consoleLogService,
        protected HttpLogService $httpLogService,
        protected ModelLogService $modelLogService,
        protected QueryLogService $queryLogService,
        protected QueueLogService $queueLogService,
        protected RouteLogService $routeLogService,
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

        $cleanupConsoleLog = $this->consoleLogService->cleanup(0);
        $cleanupHttpLog = $this->httpLogService->cleanup(0);
        $cleanupModelLog = $this->modelLogService->cleanup(0);
        $cleanupQueryLog = $this->queryLogService->cleanup(0);
        $cleanupQueueLog = $this->queueLogService->cleanup(0);
        $cleanupRouteLog = $this->routeLogService->cleanup();
        $cleanupViewLog = $this->viewLogService->cleanup(0);

        $this->telegramEnabled = (isLocal() || isProd())
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

        return self::SUCCESS;
    }
}
