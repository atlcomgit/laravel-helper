<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования роутов
 */
class RouteLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RouteLogDto $dto) {}
}
