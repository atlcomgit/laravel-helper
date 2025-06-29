<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ViewLogDto;

/**
 * Событие логирования рендеринга blade шаблонов
 */
class ViewLogEvent extends DefaultEvent
{
    public function __construct(public ViewLogDto $dto) {}
}
