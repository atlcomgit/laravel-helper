<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Events\QueryLogEvent;
use Atlcom\LaravelHelper\Services\QueryLogService;

/**
 * Задача сохранения логирования query запросов через очередь
 */
class QueryLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected QueryLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.query_log.queue'));
    }


    /**
     * Обработка задачи логирования query запросов
     *
     * @return void
     */
    public function __invoke()
    {
        app(QueryLogService::class)->log($this->dto);

        event(new QueryLogEvent($this->dto));
    }
}
