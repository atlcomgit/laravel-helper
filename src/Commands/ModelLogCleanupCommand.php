<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ModelLogService;

/**
 * Консольная команда очистки логов моделей
 */
class ModelLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:model_log';
    protected $description = 'Очистка логов моделей';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(protected ModelLogService $modelLogService)
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

        $cleanup = $this->modelLogService->cleanup(Lh::config(ConfigEnum::ModelLog, 'cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
