<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Отправка лога в телеграм через очередь
 */
class HttpLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 2;


    public function __construct(protected HttpLogDto $httpLogDto)
    {
        $this->onQueue(config('laravel-helper.http_log.queue'));
    }


    /**
     * Обработка задачи
     *
     * @return void
     */
    public function handle(HttpLogService $httpLogService): void
    {
        match ($this->httpLogDto->status) {
            HttpLogStatusEnum::Process => $httpLogService->create($this->httpLogDto),
            HttpLogStatusEnum::Success => $httpLogService->update($this->httpLogDto),
            HttpLogStatusEnum::Failed => $httpLogService->failed($this->httpLogDto),
        };
    }
}
