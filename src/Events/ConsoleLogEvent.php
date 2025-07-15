<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;

/**
 * Событие логирования консольных команд
 */
class ConsoleLogEvent extends DefaultEvent
{
    public function __construct(public ConsoleLogDto $dto) {}
}
