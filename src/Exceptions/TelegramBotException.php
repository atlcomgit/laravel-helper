<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

class TelegramBotException extends DefaultException
{
    public const CODE = 500;
}
