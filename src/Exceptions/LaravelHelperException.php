<?php

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

/**
 * Исключение пакета LaravelHelper
 */
class LaravelHelperException extends DefaultException
{
    public const CODE = 500;
}
