<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Events\HttpLogEvent;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача сохранения логирования http запросов через очередь
 */
class HttpLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;


    public function __construct(protected HttpLogDto $dto)
    {
        $this->onQueue(config('laravel-helper.http_log.queue'));
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
