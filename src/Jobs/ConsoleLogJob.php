<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\ConsoleLogEvent;
use Atlcom\LaravelHelper\Services\ConsoleLogService;

/**
 * Задача сохранения логирования консольных команд через очередь
 */
class ConsoleLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ConsoleLogDto $dto)
    {
        $this->onQueue(lhConfig(ConfigEnum::ConsoleLog, 'queue'));
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
