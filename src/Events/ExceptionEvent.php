<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\ExceptionDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования исключений
 */
class ExceptionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExceptionDto $dto) {}
}
