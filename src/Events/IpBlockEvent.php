<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\IpBlockEventDto;

/**
 * Событие блокировки ip адреса
 */
class IpBlockEvent extends DefaultEvent
{
    public function __construct(public IpBlockEventDto $dto) {}
}
