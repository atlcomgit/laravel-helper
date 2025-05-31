<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования отправки сообщения в телеграм
 */
class TelegramLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramLogDto $dto) {}
}
