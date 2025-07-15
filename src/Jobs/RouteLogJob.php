<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\RouteLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\RouteLogService;

/**
 * Задача сохранения логирования роутов через очередь
 */
class RouteLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected RouteLogDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::RouteLog, 'queue'));
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
