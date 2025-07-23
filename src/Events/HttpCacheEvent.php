<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\HttpCacheEventDto;

/**
 * Событие кеширования http запроса
 */
class HttpCacheEvent extends DefaultEvent
{
    public function __construct(public HttpCacheEventDto $dto) {}
}
