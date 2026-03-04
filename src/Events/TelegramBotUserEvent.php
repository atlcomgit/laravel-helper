<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Models\TelegramBotUser;

/**
 * Событие сохранения пользователя бота телеграм в БД
 */
class TelegramBotUserEvent extends DefaultEvent
{
    public function __construct(public TelegramBotUser $user) {}
}