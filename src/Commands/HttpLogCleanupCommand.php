<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Models\HttpLog;

class HttpLogCleanupCommand extends DefaultCommand
{
    public const CLEANUP_DAYS = 7;

    protected $signature = 'cleanup:http_logs';
    protected $description = 'Очистка логов http запросов';
    protected $isolated = true;


    /**
     * Обработчик команды
     */
    public function handle()
    {
        $this->consoleClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $deleted = HttpLog::query()
            ->whereDate('created_at', '<', now()->subDays(self::CLEANUP_DAYS))
            ->delete();

        $this->telegramEnabled = (isLocal() || isProd()) && $deleted > 0;
        $this->telegramComment = "Удалено {$deleted} " . trans_choice('запись|записи|записей', $deleted);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
