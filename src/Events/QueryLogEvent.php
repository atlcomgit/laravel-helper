<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования query запросов
 */
class QueryLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public QueryLogDto $dto) {}
}
