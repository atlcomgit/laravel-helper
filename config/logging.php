<?php

use Atlcom\LaravelHelper\Listeners\TelegramLogger;

return [
    'channels' => [
        /**
         * TelegramLog
         */
        'telegram_log' => [
            'driver' => 'custom',
            'via' => TelegramLogger::class,
        ],
    ],
];
