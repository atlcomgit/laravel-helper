<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\QueryLogService;

/**
 * Консольная команда очистки логов query запросов
 */
class QueryLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:query_logs';
    protected $description = 'Очистка логов query запросов';
    protected $isolated = true;


    public function __construct(protected QueryLogService $queryLogService)
    {
        parent::__construct();
    }


    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        $this->outputClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->queryLogService->cleanup(config('laravel-helper.query_log.cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
