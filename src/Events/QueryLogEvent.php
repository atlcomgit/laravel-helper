<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\QueryLogDto;

/**
 * Событие логирования query запросов
 */
class QueryLogEvent extends DefaultEvent
{
    public function __construct(public QueryLogDto $dto) {}
}
