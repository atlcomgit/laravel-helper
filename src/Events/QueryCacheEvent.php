<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\QueryCacheEventDto;

/**
 * Событие кеширования query запросов
 */
class QueryCacheEvent extends DefaultEvent
{
    public function __construct(public QueryCacheEventDto $dto) {}
}
