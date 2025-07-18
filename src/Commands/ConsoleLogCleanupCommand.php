<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ConsoleLogService;

/**
 * Консольная команда очистки логов консольных команд
 */
class ConsoleLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:console_log';
    protected $description = 'Очистка логов консольных команд';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(protected ConsoleLogService $consoleLogService)
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

        $cleanup = $this->consoleLogService->cleanup(Lh::config(ConfigEnum::ConsoleLog, 'cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
