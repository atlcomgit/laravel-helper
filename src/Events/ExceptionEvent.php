<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ExceptionDto;

/**
 * Событие логирования исключений
 */
class ExceptionEvent extends DefaultEvent
{
    public function __construct(public ExceptionDto $dto) {}
}
