<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\CommandTrait;
use Illuminate\Console\Command;

/**
 * Абстрактный класс для консольных команд
 */
abstract class DefaultCommand extends Command
{
    use CommandTrait;
}
