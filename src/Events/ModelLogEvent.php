<?php

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ModelLogDto;

/**
 * Событие логирования моделей
 */
class ModelLogEvent extends DefaultEvent
{
    public function __construct(public ModelLogDto $dto) {}
}
