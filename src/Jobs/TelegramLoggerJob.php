<?php

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Отправка лога в телеграм через очередь
 */
class TelegramLoggerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public const FAILED_REPEAT_COUNT = 1;
    public const FAILED_REPEAT_DELAY = 60;


    public function __construct(
        protected TelegramLogDto $dto,
        protected ?TelegramService $telegramService = null,
    ) {
        $this->telegramService ??= app(TelegramService::class);
    }


    /**
     * Обработка задачи
     *
     * @return void
     */
    public function handle()
    {
        $sendResult = $this->telegramService->sendMessage($this->dto);

        // Повторная попытка задачи
        if (!$sendResult && $this->attempts() <= static::FAILED_REPEAT_COUNT && !isLocal()) {
            $this->release(static::FAILED_REPEAT_DELAY);
        }
    }


    /**
     * Ограничение повторного запуска
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            // new WithoutOverlapping($this->dto->getHash()),
            // (new ThrottlesExceptions(1, 1))->backoff(static::FAILED_REPEAT_DELAY),
        ];
    }
}
