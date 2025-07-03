<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Events\HttpLogEvent;
use Atlcom\LaravelHelper\Services\HttpLogService;

/**
 * Задача сохранения логирования http запросов через очередь
 */
class HttpLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected HttpLogDto $dto)
    {
        $this->onQueue(lhConfig(ConfigEnum::HttpLog, 'queue'));
    }


    /**
     * Обработка задачи
     *
     * @return void
     */
    public function __invoke(HttpLogService $httpLogService): void
    {
        match ($this->dto->status) {
            HttpLogStatusEnum::Process => $httpLogService->create($this->dto),
            HttpLogStatusEnum::Success => $httpLogService->update($this->dto),
            HttpLogStatusEnum::Failed => $httpLogService->failed($this->dto),
        };

        event(new HttpLogEvent($this->dto));
    }
}
