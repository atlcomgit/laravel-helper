<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\ConsoleLogService;

/**
 * Консольная команда очистки логов консольных команд
 */
class ConsoleLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'cleanup:console_logs';
    protected $description = 'Очистка логов консольных команд';
    protected $isolated = true;


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
        $this->outputClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->consoleLogService->cleanup(config('laravel-helper.console_log.cleanup_days'));

        $this->telegramEnabled = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Helper::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
