<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Абстрактный класс для событий
 */
abstract class DefaultEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
