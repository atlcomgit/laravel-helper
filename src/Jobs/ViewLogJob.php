<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Events\ViewLogEvent;
use Atlcom\LaravelHelper\Services\ViewLogService;

/**
 * Задача сохранения логирования рендеринга blade шаблонов через очередь
 */
class ViewLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ViewLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.view_log.queue'));
    }


    /**
     * Обработка задачи логирования рендеринга blade шаблонов
     *
     * @return void
     */
    public function __invoke()
    {
        app(ViewLogService::class)->log($this->dto);

        event(new ViewLogEvent($this->dto));
    }
}
