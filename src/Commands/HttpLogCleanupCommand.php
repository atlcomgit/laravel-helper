<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\HttpLogService;

/**
 * Консольная команда очистки логов http запросов
 */
class HttpLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:http_log';
    protected $description = 'Очистка логов http запросов';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


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
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->httpLogService->cleanup(Lh::config(ConfigEnum::HttpLog, 'cleanup_days'));

        $this->telegramLog = (isLocal() || isDev() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
