<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Models\TelegramBotChat;

/**
 * Событие сохранения чата бота телеграм в БД
 */
class TelegramBotChatEvent extends DefaultEvent
{
    public function __construct(public TelegramBotChat $chat) {}
}