<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Events\QueueLogEvent;
use Atlcom\LaravelHelper\Services\QueueLogService;

/**
 * Задача сохранения логирования задач через очередь
 */
class QueueLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected QueueLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.queue_log.queue'));
    }


    /**
     * Обработка задачи логирования консольных команд
     *
     * @return void
     */
    public function __invoke()
    {
        app(QueueLogService::class)->log($this->dto);

        event(new QueueLogEvent($this->dto));
    }
}
