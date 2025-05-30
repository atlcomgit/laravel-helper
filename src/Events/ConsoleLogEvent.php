<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования консольных команд
 */
class ConsoleLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ConsoleLogDto $dto) {}
}
