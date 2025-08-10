<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotMemberDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotListenerService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @internal
 * Слушатель события входящих/исходящих сообщений телеграм бота
 * 
 * События:
 * @see TelegramBotEvent
 */
class TelegramBotEventListener extends DefaultListener implements ShouldQueue
{
    public function __construct(
        private TelegramBotListenerService $telegramBotListenerService,
    ) {
        $this->queue = Lh::config(ConfigEnum::TelegramBot, 'queue');
    }


    /**
     * Обработчик слушателя
     *
     * @param TelegramBotEvent $event
     * @return void
     */
    public function __invoke(TelegramBotEvent $event): void
    {
        match (true) {
            $event->dto instanceof TelegramBotInDto => $this->telegramBotListenerService->incoming($event->dto),
            $event->dto instanceof TelegramBotOutDto => $this->telegramBotListenerService->outgoing($event->dto),
            $event->dto instanceof TelegramBotMemberDto => $this->telegramBotListenerService->member($event->dto),
        };
    }
}
