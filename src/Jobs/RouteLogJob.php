<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Events\RouteLogEvent;
use Atlcom\LaravelHelper\Services\RouteLogService;

/**
 * Задача сохранения логирования роутов через очередь
 */
class RouteLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected RouteLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.route_log.queue'));
    }


    /**
     * Обработка задачи логирования изменений у модели
     *
     * @return void
     */
    public function __invoke()
    {
        app(RouteLogService::class)->log($this->dto);

        event(new RouteLogEvent($this->dto));
    }
}
