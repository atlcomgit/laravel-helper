<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\ProfilerLogEvent;
use Atlcom\LaravelHelper\Services\ProfilerLogService;

/**
 * Задача сохранения логирования профилирования методов класса через очередь
 */
class ProfilerLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ProfilerLogDto $dto)
    {
        $this->onQueue(lhConfig(ConfigEnum::ProfilerLog, 'queue'));
    }


    /**
     * Обработка задачи логирования консольных команд
     *
     * @return void
     */
    public function __invoke()
    {
        app(ProfilerLogService::class)->log($this->dto);

        event(new ProfilerLogEvent($this->dto));
    }
}
