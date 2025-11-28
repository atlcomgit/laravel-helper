<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;

/**
 * Консольная команда очистки логов отправки писем
 */
class MailLogCleanupCommand extends DefaultCommand
{
    protected       $signature       = 'lh:cleanup:mail_log';
    protected       $description     = 'Очистка логов отправки писем от laravel-helper';
    protected       $isolated        = true;
    protected ?bool $withConsoleLog  = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(protected MailLogService $mailLogService)
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

        $cleanup = $this->mailLogService->cleanup(Lh::config(ConfigEnum::MailLog, 'cleanup_days'));

        $this->telegramLog = (isLocal() || isDev() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
