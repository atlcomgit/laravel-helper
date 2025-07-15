<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\QueueLogDto;

/**
 * Событие логирования очередей
 */
class QueueLogEvent extends DefaultEvent
{
    public function __construct(public QueueLogDto $dto) {}
}
