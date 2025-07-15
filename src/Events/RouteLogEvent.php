<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\RouteLogDto;

/**
 * Событие логирования роутов
 */
class RouteLogEvent extends DefaultEvent
{
    public function __construct(public RouteLogDto $dto) {}
}
