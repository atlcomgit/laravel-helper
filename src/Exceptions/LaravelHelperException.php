<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

/**
 * @internal
 * Исключение пакета LaravelHelper
 */
class LaravelHelperException extends DefaultException
{
    public const CODE = 500;
}
