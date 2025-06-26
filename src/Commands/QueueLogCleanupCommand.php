<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\QueueLogService;

/**
 * Консольная команда очистки логов очередей
 */
class QueueLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:queue_log';
    protected $description = 'Очистка логов очередей';
    protected $isolated = true;


    public function __construct(protected QueueLogService $queueLogService)
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

        $cleanup = $this->queueLogService->cleanup(config('laravel-helper.queue_log.cleanup_days'));

        $this->telegramLog = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Удалено ' . Hlp::stringPlural($cleanup, ['записей', 'запись', 'записи']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
