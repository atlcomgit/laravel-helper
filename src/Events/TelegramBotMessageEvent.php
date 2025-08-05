<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;

/**
 * Событие сохранения сообщения бота телеграм в БД
 */
class TelegramBotMessageEvent extends DefaultEvent
{
    public function __construct(public TelegramBotMessage $message) {}
}
