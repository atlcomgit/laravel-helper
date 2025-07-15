<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

/**
 * Исключение без отправки сообщения в телеграм
 */
class WithoutTelegramException extends DefaultException
{
}
