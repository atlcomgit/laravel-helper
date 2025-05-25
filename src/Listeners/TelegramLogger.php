<?php

namespace Atlcom\LaravelHelper\Listeners;

use Monolog\Logger;

class TelegramLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger(config('app.name'), [
            new TelegramLoggerHandler(),
        ]);
    }
}