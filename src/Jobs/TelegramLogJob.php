<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\TelegramLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramLogService;

/**
 * Задача отправки сообщений в телеграм через очередь
 */
class TelegramLogJob extends DefaultJob
{
    public const FAILED_REPEAT_COUNT = 1;
    public const FAILED_REPEAT_DELAY = 60;


    public function __construct(
        protected TelegramLogDto $dto,
        protected ?TelegramLogService $telegramLogService = null,
    ) {
        $this->onQueue(Lh::config(ConfigEnum::TelegramLog, 'queue'));
        $this->telegramLogService ??= app(TelegramLogService::class);
    }


    /**
     * Обработка задачи
     *
     * @return void
     */
    public function __invoke()
    {
        $sendResult = $this->telegramLogService->sendMessage($this->dto);

        // Повторная попытка задачи
        if (isProd() && !$sendResult && $this->attempts() <= static::FAILED_REPEAT_COUNT) {
            $this->release(static::FAILED_REPEAT_DELAY);
        } else {
            event(new TelegramLogEvent($this->dto));
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
