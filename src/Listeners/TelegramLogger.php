<?php

namespace Atlcom\LaravelHelper\Listeners;

use Monolog\Logger;

/**
 * Связь логирования с обработчиком
 */
class TelegramLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger(config('app.name'), [new TelegramLoggerHandler()]);
    }
}
