<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;

/**
 * Событие отправки сообщения в бота телеграм
 */
class TelegramBotEvent extends DefaultEvent
{
    public function __construct(public TelegramBotDto $dto) {}
}
