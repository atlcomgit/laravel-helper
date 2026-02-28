<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\IpBlockService;

/**
 * Консольная команда очистки устаревших ip блокировок
 */
class IpBlockCleanupCommand extends DefaultCommand
{
    protected       $signature       = 'lh:cleanup:ip_block';
    protected       $description     = 'Очистка устаревших ip блокировок от laravel-helper';
    protected       $isolated        = true;
    protected ?bool $withConsoleLog  = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(protected IpBlockService $ipBlockService)
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
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->ipBlockService->cleanupExpired();
        $text = 'Удалено ' . Hlp::stringPlural($cleanup, ['блокировок', 'блокировка', 'блокировки']);

        $this->outputEol($text, 'fg=green');

        return self::SUCCESS;
    }
}
