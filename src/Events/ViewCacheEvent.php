<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ViewCacheEventDto;

/**
 * Событие кеширования рендеринга blade шаблонов
 */
class ViewCacheEvent extends DefaultEvent
{
    public function __construct(public ViewCacheEventDto $dto) {}
}
