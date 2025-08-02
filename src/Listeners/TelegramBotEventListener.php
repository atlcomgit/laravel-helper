<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotListenerService;

/**
 * Слушатель события входящих/исходящих сообщений телеграм бота
 * 
 * События:
 * @see TelegramBotEvent
 */
class TelegramBotEventListener extends DefaultListener
{
    public function __construct(
        private TelegramBotListenerService $telegramBotListenerService,
    ) {}


    /**
     * Обработчик слушателя
     *
     * @param TelegramBotEvent $event
     * @return void
     */
    public function __invoke(TelegramBotEvent $event): void
    {
        match ($event->dto::class) {
            TelegramBotInDto::class => $this->telegramBotListenerService->incoming($event->dto),
            TelegramBotOutDto::class => $this->telegramBotListenerService->outgoing($event->dto),
        };
    }
}
