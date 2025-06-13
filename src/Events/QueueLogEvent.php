<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования очередей
 */
class QueueLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public QueueLogDto $dto) {}
}
