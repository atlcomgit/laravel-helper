<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\ConsoleLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ConsoleLogService;

/**
 * @internal
 * Задача сохранения логирования консольных команд через очередь
 */
class ConsoleLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ConsoleLogDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::ConsoleLog, 'queue'));
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
