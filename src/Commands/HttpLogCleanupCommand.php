<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\HttpLogService;

/**
 * Консольная команда очистки логов http запросов
 */
class HttpLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'cleanup:http_logs';
    protected $description = 'Очистка логов http запросов';
    protected $isolated = true;


    public function __construct(protected HttpLogService $httpLogService)
    {
        parent::__construct();
    }


    /**
     * Обработчик команды
     * 
     * @return int
     */
    public function handle()
    {
        $this->outputClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->httpLogService->cleanup(config('laravel-helper.http_log.cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
