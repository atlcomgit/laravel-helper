<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Loggers;

use Atlcom\LaravelHelper\Defaults\DefaultLogger;
use Monolog\Logger;
use Atlcom\LaravelHelper\Handlers\TelegramLogHandler;

/**
 * Связь логирования с обработчиком
 */
class TelegramLogLogger extends DefaultLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger(config('app.name'), [new TelegramLogHandler()]);
    }
}
