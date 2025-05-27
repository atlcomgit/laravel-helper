<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Models\ModelLog;

class ModelLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'cleanup:model_logs';
    protected $description = 'Очистка логов моделей';
    protected $isolated = true;


    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        $this->consoleClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $deleted = ModelLog::query()
            ->whereDate('created_at', '<', now()->subDays(config('laravel-helper.model_log.cleanup_days')))
            ->delete();

        $this->telegramEnabled = (isLocal() || isProd()) && $deleted > 0;
        $this->telegramComment = 'Удалено ' . Helper::stringPlural($deleted, ['запись', 'записи', 'записей']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
