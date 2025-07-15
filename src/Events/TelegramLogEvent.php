<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;

/**
 * Событие логирования отправки сообщения в телеграм
 */
class TelegramLogEvent extends DefaultEvent
{
    public function __construct(public TelegramLogDto $dto) {}
}
