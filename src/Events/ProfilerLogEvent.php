<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;

/**
 * Событие логирования профилирования методов класса
 */
class ProfilerLogEvent extends DefaultEvent
{
    public function __construct(public ProfilerLogDto $dto) {}
}
