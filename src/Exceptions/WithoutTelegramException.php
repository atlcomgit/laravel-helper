<?php

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

/**
 * Исключение без отправки сообщения в телеграм
 */
class WithoutTelegramException extends DefaultException
{
}
