<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\HttpLogDto;

/**
 * Событие логирования http запросов
 */
class HttpLogEvent extends DefaultEvent
{
    public function __construct(public HttpLogDto $dto) {}
}
