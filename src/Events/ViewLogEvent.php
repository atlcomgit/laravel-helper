<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие логирования рендеринга blade шаблонов
 */
class ViewLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ViewLogDto $dto) {}
}
