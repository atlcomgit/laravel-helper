<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования http запросов
 */
class HttpLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public HttpLogDto $dto) {}
}
