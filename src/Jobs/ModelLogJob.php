<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Events\ModelLogEvent;
use Atlcom\LaravelHelper\Services\ModelLogService;

/**
 * Задача сохранения логирования моделей через очередь
 */
class ModelLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ModelLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.model_log.queue'));
    }


    /**
     * Обработка задачи логирования изменений у модели
     *
     * @return void
     */
    public function __invoke()
    {
        app(ModelLogService::class)->log($this->dto);

        event(new ModelLogEvent($this->dto));
    }
}
