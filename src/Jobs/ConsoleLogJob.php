<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Events\ConsoleLogEvent;
use Atlcom\LaravelHelper\Services\ConsoleLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача сохранения логирования консольных команд через очередь
 */
class ConsoleLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 1;


    public function __construct(protected ConsoleLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.console_log.queue'));
    }


    /**
     * Обработка задачи логирования консольных команд
     *
     * @return void
     */
    public function __invoke()
    {
        app(ConsoleLogService::class)->log($this->dto);

        event(new ConsoleLogEvent($this->dto));
    }
}
