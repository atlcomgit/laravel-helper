<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Events;

use Atlcom\LaravelHelper\Defaults\DefaultEvent;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Throwable;

/**
 * Событие ошибки отправки письма
 */
class MailFailed extends DefaultEvent
{
    public function __construct(public MailLogDto $dto, public Throwable $exception) {}
}
