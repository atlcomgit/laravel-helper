<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Events\RouteLogEvent;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача сохранения логирования роутов через очередь
 */
class RouteLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


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
