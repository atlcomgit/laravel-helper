<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\IpBlockEventDto;

/**
 * Событие блокировки ip адреса
 *
 * Слушатели:
 * @see \App\Domains\Crm\IpBlock\Listeners\IpBlockEventListener
 */
class IpBlockEvent extends DefaultEvent
{
    public function __construct(public IpBlockEventDto $dto) {}
}
