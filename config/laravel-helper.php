<?php

/**
 * laravel-helper config
 */
return [
    /**
     * Str. Включение макросов хелпера
     */
    'str' => [
        'macros-enabled' => env('STR_MACROS_ENABLED', true),
    ],

    /**
     * HttpLog. Логирование http запросов
     */
    'http_log' => [
        'only_response' => env('HTTP_LOG_ONLY_RESPONSE', true),
        'in' => [
            'enabled' => env('HTTP_LOG_IN_ENABLED', env('HTTP_LOG_ENABLED', false)),
        ],
        'out' => [
            'enabled' => env('HTTP_LOG_OUT_ENABLED', env('HTTP_LOG_ENABLED', false)),
        ],
    ],

    /**
     * TelegramLog. Логирование в телеграм
     */
    'telegram_log' => [
        // Вкл/Выкл отправки в телеграм
        'enabled' => (bool)env('TELEGRAM_ENABLED', true),

        // Токен бота
        'token' => env('TELEGRAM_API_TOKEN', env('TELEGRAM_BOT_TOKEN')),

        // Настройка отправки логов информации
        'info' => [
            // Вкл/Выкл отправки информации
            'enabled' => (bool)env('TELEGRAM_INFO_ENABLED', true),
            // Telegram chat id для информации
            'chat_id' => env('TELEGRAM_INFO_CHAT_ID'),
            // Токен бота для информации
            'token' => env('TELEGRAM_INFO_TOKEN', env('TELEGRAM_API_TOKEN', env('TELEGRAM_BOT_TOKEN'))),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => env('TELEGRAM_INFO_CACHE_TTL', '0 seconds'),
        ],

        // Настройка отправки логов ошибок
        'error' => [
            // Вкл/Выкл отправки ошибок
            'enabled' => (bool)env('TELEGRAM_ERROR_ENABLED', true),
            // Telegram chat id для ошибок
            'chat_id' => env('TELEGRAM_ERROR_CHAT_ID'),
            // Токен бота для ошибок
            'token' => env('TELEGRAM_ERROR_TOKEN', env('TELEGRAM_API_TOKEN', env('TELEGRAM_BOT_TOKEN'))),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => env('TELEGRAM_ERROR_CACHE_TTL', '5 minutes'),
        ],

        // Настройка отправки логов предупреждений
        'warning' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('TELEGRAM_WARNING_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => env('TELEGRAM_WARNING_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => env('TELEGRAM_WARNING_TOKEN', env('TELEGRAM_API_TOKEN', env('TELEGRAM_BOT_TOKEN'))),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => env('TELEGRAM_WARNING_CACHE_TTL', '5 seconds'),
        ],

        // Настройка отправки логов отладки
        'debug' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('TELEGRAM_DEBUG_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => env('TELEGRAM_DEBUG_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => env('TELEGRAM_DEBUG_TOKEN', env('TELEGRAM_API_TOKEN', env('TELEGRAM_BOT_TOKEN'))),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => env('TELEGRAM_DEBUG_CACHE_TTL', '5 seconds'),
        ],
    ],
];
