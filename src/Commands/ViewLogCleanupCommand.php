<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ViewLogService;

/**
 * Консольная команда очистки логов рендеринга blade шаблонов
 */
class ViewLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:view_log';
    protected $description = 'Очистка логов рендеринга blade шаблонов от laravel-helper';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


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
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->viewLogService->cleanup(Lh::config(ConfigEnum::ViewLog, 'cleanup_days'));

        $this->telegramLog = (isLocal() || isDev() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
