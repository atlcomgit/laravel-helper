<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;

/**
 * Событие входящего/исходящего сообщения бота телеграм (webhook, send)
 * 
 * Слушатели:
 * @see \Atlcom\LaravelHelper\Listeners\TelegramBotEventListener
 */
class TelegramBotEvent extends DefaultEvent
{
    public function __construct(public TelegramBotDto $dto) {}
}
