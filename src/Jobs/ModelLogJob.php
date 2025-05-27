<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Services\ModelLogJobService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModelLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 2;


    public function __construct(protected ModelLogDto $httpLogDto)
    {
        $this->onQueue(config('laravel-helper.model_log.queue'));
    }


    /**
     * Обработка задачи логирования изменений у модели
     *
     * @return void
     */
    public function handle()
    {
        (new ModelLogJobService($this->httpLogDto))->handle();
    }
}
