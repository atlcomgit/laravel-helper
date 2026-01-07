<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Throwable;

/**
 * @internal
 * @see \Illuminate\Http\Client\Events\ConnectionFailed
 */
class HttpConnectionFailedListener extends DefaultListener
{
    public function __construct(
        private HttpLogService $httpLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Обрабатывает событие ошибки подключения http клиента.
     *
     * @param object $event
     * @return void
     */
    public function __invoke(object $event): void
    {
        $uuid = ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null;
        $throwable = ($event->exception ?? null) instanceof Throwable ? $event->exception : null;

        $telegramAttempt = ($event->request?->header('X-Telegram-Attempt') ?? [])[0] ?? null;
        $telegramTimeout = ($event->request?->header('X-Telegram-Timeout') ?? [])[0] ?? null;
        $telegramConnectTimeout = ($event->request?->header('X-Telegram-Connect-Timeout') ?? [])[0] ?? null;
        $telegramMaxAttempts = ($event->request?->header('X-Telegram-Max-Attempts') ?? [])[0] ?? null;

        // Если это не последняя попытка Telegram-ретрая — не пишем ошибку в логи,
        // т.к. следующая попытка часто успешно отправляет сообщение.
        // if (
        //     $telegramAttempt !== null
        //     && $telegramMaxAttempts !== null
        //     && (int)$telegramAttempt < (int)$telegramMaxAttempts
        // ) {
        //     return;
        // }

        $dto = HttpLogDto::createByRequest(
            uuid: $uuid,
            request: $event->request,
            info: [
                'connection_failed' => [
                    'exception'          => $throwable ? $throwable::class : null,
                    'message'            => $throwable?->getMessage(),
                    'code'               => $throwable?->getCode(),
                    'previous_exception' => $throwable?->getPrevious() ? $throwable->getPrevious()::class : null,
                    'previous_message'   => $throwable?->getPrevious()?->getMessage(),
                ],
                'telegram'          => array_filter([
                    'attempt'                 => $telegramAttempt,
                    'timeout_seconds'         => $telegramTimeout,
                    'connect_timeout_seconds' => $telegramConnectTimeout,
                    'max_attempts'            => $telegramMaxAttempts,
                ], static fn ($v) => $v !== null),
            ],
        )
            ->merge([
                'status'          => HttpLogStatusEnum::Failed,
                'responseMessage' => 'Connection failed',
            ]);

        !$dto->uuid ?: $dto->dispatch();
    }
}
