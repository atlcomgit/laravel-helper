<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования моделей
 */
class ModelLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ModelLogDto $dto) {}
}
