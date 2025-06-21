<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\ViewLogService;

/**
 * Консольная команда очистки логов рендеринга blade шаблонов
 */
class ViewLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:view_logs';
    protected $description = 'Очистка логов рендеринга blade шаблонов';
    protected $isolated = true;


    public function __construct(protected ViewLogService $viewLogService)
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

        $cleanup = $this->viewLogService->cleanup(config('laravel-helper.view_log.cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
