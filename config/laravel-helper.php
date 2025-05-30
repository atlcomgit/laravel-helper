<?php

use Atlcom\LaravelHelper\Enums\ModelLogDriverEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Foundation\Auth\User;


/**
 * laravel-helper config
 */
return [
    /**
     * Debug. Настройки вывода debug информации при ошибках
     */
    'app' => [
        'debug' => (bool)env('APP_DEBUG', false),
        'debug_data' => (bool)env('APP_DEBUG_DATA', false),
        'debug_trace' => (bool)env('APP_DEBUG_TRACE', false),
        'debug_trace_vendor' => (bool)env('APP_DEBUG_TRACE_VENDOR', false),
    ],


    /**
     * Testing. Авто-тестирование
     */
    'testing' => [
        'email' => 'test@test.ru',
    ],


    /**
     * Macro. Включение макросов хелпера
     */
    'macros' => [
        'str' => [
            'enabled' => (bool)env('STR_MACROS_ENABLED', true),
        ],
        'http' => [
            'enabled' => (bool)env('HTTP_MACROS_ENABLED', true),
        ],
    ],


    /**
     * ConsoleLog. Логирование консольных команд
     */
    'console_log' => [
        'enabled' => (bool)env('CONSOLE_LOG_ENABLED', false),
        'queue' => env('CONSOLE_LOG_QUEUE', 'default'),
        'connection' => env('CONSOLE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => env('CONSOLE_LOG_TABLE', 'route_logs'),
        'model' => ConsoleLog::class,
        'cleanup_days' => (int)env('CONSOLE_LOG_CLEANUP_DAYS', 7),
        'store_on_start' => (bool)env('CONSOLE_LOG_STORE_ON_START', true),
        'store_interval_seconds' => (int)env('CONSOLE_LOG_STORE_INTERVAL_SECONDS', 3),
    ],


    /**
     * HttpLog. Логирование http запросов
     */
    'http_log' => [
        'queue' => env('HTTP_LOG_QUEUE', 'default'),
        'connection' => env('HTTP_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => env('HTTP_LOG_TABLE', 'http_logs'),
        'model' => HttpLog::class,
        'user' => [
            'table_name' => (new User())->getTable(), // Название таблицы модели User
            'primary_key' => (new User())->getKeyName(), // Название первичного ключа модели User
            'primary_type' => match ((new User())->getKeyType()) { // Тип первичного ключа модели User
                'int', 'integer' => 'bigInteger',
                'string', 'uuid' => 'uuid',

                default => 'text',
            },
        ],
        'only_response' => (bool)env('HTTP_LOG_ONLY_RESPONSE', true),
        'in' => [
            'enabled' => (bool)env('HTTP_LOG_IN_ENABLED', env('HTTP_LOG_ENABLED', false)),
        ],
        'out' => [
            'enabled' => (bool)env('HTTP_LOG_OUT_ENABLED', env('HTTP_LOG_ENABLED', false)),
        ],
        'cleanup_days' => (int)env('HTTP_LOG_CLEANUP_DAYS', 7),
    ],


    /**
     * ModelLog. Логирование моделей
     */
    'model_log' => [
        'enabled' => (bool)env('MODEL_LOG_ENABLED', false),
        'queue' => env('MODEL_LOG_QUEUE', 'default'),
        'connection' => env('MODEL_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => env('MODEL_LOG_TABLE', 'model_logs'),
        'model' => ModelLog::class,
        'user' => [
            'table_name' => (new User())->getTable(), // Название таблицы модели User
            'primary_key' => (new User())->getKeyName(), // Название первичного ключа модели User
            'primary_type' => match ((new User())->getKeyType()) { // Тип первичного ключа модели User
                'int', 'integer' => 'bigInteger',
                'string', 'uuid' => 'uuid',

                default => 'text',
            },
        ],
        'drivers' => explode(',', env('MODEL_LOG_DRIVERS', ModelLogDriverEnum::Database->value)),
        'file' => env('MODEL_LOG_FILE', storage_path('logs/model.log')),
        'cleanup_days' => (int)env('MODEL_LOG_CLEANUP_DAYS', 7),
    ],


    /**
     * RouteLog. Логирование роутов
     */
    'route_log' => [
        'enabled' => (bool)env('ROUTE_LOG_ENABLED', false),
        'queue' => env('ROUTE_LOG_QUEUE', 'default'),
        'connection' => env('ROUTE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => env('ROUTE_LOG_TABLE', 'route_logs'),
        'model' => RouteLog::class,
    ],


    /**
     * TelegramLog. Логирование в телеграм
     */
    'telegram_log' => [
        // Вкл/Выкл отправки в телеграм
        'enabled' => (bool)env('TELEGRAM_LOG_ENABLED', true),
        'queue' => env('TELEGRAM_LOG_QUEUE', 'default'),

        // Токен бота
        'token' => env('TELEGRAM_LOG_TOKEN'),

        // Настройка отправки логов информации
        'info' => [
            // Вкл/Выкл отправки информации
            'enabled' => (bool)env('TELEGRAM_INFO_ENABLED', true),
            // Telegram chat id для информации
            'chat_id' => env('TELEGRAM_INFO_CHAT_ID'),
            // Токен бота для информации
            'token' => env('TELEGRAM_INFO_TOKEN', env('TELEGRAM_LOG_TOKEN')),
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
            'token' => env('TELEGRAM_ERROR_TOKEN', env('TELEGRAM_LOG_TOKEN')),
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
            'token' => env('TELEGRAM_WARNING_TOKEN', env('TELEGRAM_LOG_TOKEN')),
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
            'token' => env('TELEGRAM_DEBUG_TOKEN', env('TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => env('TELEGRAM_DEBUG_CACHE_TTL', '5 seconds'),
        ],
    ],


    'http' => [
        'smsRu' => [
            'enabled' => (bool)env('HTTP_SMSRU_ENABLED', false),
            'url' => env('HTTP_SMSRU_URL', 'https://sms.ru'),
            'api_key' => env('HTTP_SMSRU_API_KEY'),
            'from' => env('HTTP_SMSRU_FROM'),
            'to' => env('HTTP_SMSRU_TO'),
            'send_ip_address' => (bool)env('HTTP_SMSRU_SEND_IP_ADDRESS', false),
        ],

        'mangoOfficeRu' => [
            'enabled' => (bool)env('HTTP_MANGOOFFICERU_ENABLED', false),
            'url' => env('HTTP_MANGOOFFICERU_URL', 'https://app.mango-office.ru/vpbx/'),
            'api_key' => env('HTTP_MANGOOFFICERU_API_KEY', ''),
            'api_salt' => env('HTTP_MANGOOFFICERU_API_SALT', ''),
            'webhook_token' => env('HTTP_MANGOOFFICERU_WEBHOOK_TOKEN', 'mango_token'),
        ],

        'devlineRu' => [
            'enabled' => (bool)env('HTTP_DEVLINERU_ENABLED', false),
            'url' => [
                'http' => env('HTTP_DEVLINERU_HTTP_URL', 'http://btAAAAA.loc.devline.tv:XXXX'),
                'rtsp' => env('HTTP_DEVLINERU_RTSP_URL', 'rtsp://btAAAAA.loc.devline.tv:YYYY'),
            ],
            'timeout' => env('HTTP_DEVLINERU_TIMEOUT', 10),
            'authorization' => env('HTTP_DEVLINERU_AUTHORIZATION', ''),
        ],

        'rtspMe' => [
            'enabled' => (bool)env('HTTP_RTSPME_ENABLED', false),
            'url' => env('HTTP_RTSPME_URL', 'https://rtsp.me'),
            'timeout' => env('HTTP_RTSPME_TIMEOUT', 10),
            'auth' => [
                'email' => env('HTTP_RTSPME_EMAIL'),
                'password' => env('HTTP_RTSPME_PASSWORD'),
            ],
            'embed_url' => 'https://rtsp.me/embed/{rtspme_id}/',
        ],

        'fcmGoogleApisCom' => [
            'enabled' => (bool)env('HTTP_FCMGOOGLEAPISCOM_ENABLED', false),
            'url' => env('HTTP_FCMGOOGLEAPISCOM_URL', 'https://fcm.googleapis.com/v1/'),
            'firebase_credentials' => env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_CREDENTIALS', ''),
            'project_id' => env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_PROJECT', ''),
            'timeout' => env('HTTP_FCMGOOGLEAPISCOM_TIMEOUT', 30),
        ],

        'telegramOrg' => [
            'enabled' => (bool)env('HTTP_TELEGRAMORG_ENABLED', true),
            'url' => env('HTTP_TELEGRAMORG_URL', 'https://api.telegram.org/'),
            'timeout' => env('HTTP_TELEGRAMORG_TIMEOUT', 10),
        ],
    ],
];
