<?php

use Atlcom\Helper;
use Atlcom\LaravelHelper\Enums\ModelLogDriverEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Models\ViewLog;
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
        'builder' => [
            'enabled' => (bool)env('BUILDER_MACROS_ENABLED', true),
        ],
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
        'queue' => (string)env('CONSOLE_LOG_QUEUE', 'default'),
        'connection' => (string)env('CONSOLE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('CONSOLE_LOG_TABLE', 'helper_route_logs'),
        'model' => ConsoleLog::class,
        'cleanup_days' => (int)env('CONSOLE_LOG_CLEANUP_DAYS', 7),
        'store_on_start' => (bool)env('CONSOLE_LOG_STORE_ON_START', false),
        'store_interval_seconds' => (int)env('CONSOLE_LOG_STORE_INTERVAL_SECONDS', 3),
        'exclude' => (array)(Helper::envGet('CONSOLE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * HttpLog. Логирование http запросов
     */
    'http_log' => [
        'queue' => (string)env('HTTP_LOG_QUEUE', 'default'),
        'connection' => (string)env('HTTP_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('HTTP_LOG_TABLE', 'helper_http_logs'),
        'model' => HttpLog::class,
        'user' => [
            'table_name' => (string)(new User())->getTable(), // Название таблицы модели User
            'primary_key' => (string)(new User())->getKeyName(), // Название первичного ключа модели User
            'primary_type' => (string)match ((new User())->getKeyType()) { // Тип первичного ключа модели User
                'int', 'integer' => 'bigInteger',
                'string', 'uuid' => 'uuid',

                default => 'text',
            },
        ],
        'only_response' => (bool)env('HTTP_LOG_ONLY_RESPONSE', true),
        'in' => [
            'enabled' => (bool)env('HTTP_LOG_IN_ENABLED', env('HTTP_LOG_ENABLED', false)),
            'exclude' => (array)(Helper::envGet('HTTP_LOG_IN_EXCLUDE', base_path('.env')) ?? []),
        ],
        'out' => [
            'enabled' => (bool)env('HTTP_LOG_OUT_ENABLED', env('HTTP_LOG_ENABLED', false)),
            'exclude' => (array)(Helper::envGet('HTTP_LOG_OUT_EXCLUDE', base_path('.env')) ?? []),
        ],
        'cleanup_days' => (int)env('HTTP_LOG_CLEANUP_DAYS', 7),

    ],


    /**
     * ModelLog. Логирование моделей
     */
    'model_log' => [
        'enabled' => (bool)env('MODEL_LOG_ENABLED', false),
        'queue' => (string)env('MODEL_LOG_QUEUE', 'default'),
        'connection' => (string)env('MODEL_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('MODEL_LOG_TABLE', 'helper_model_logs'),
        'model' => ModelLog::class,
        'user' => [
            'table_name' => (string)(new User())->getTable(), // Название таблицы модели User
            'primary_key' => (string)(new User())->getKeyName(), // Название первичного ключа модели User
            'primary_type' => (string)match ((new User())->getKeyType()) { // Тип первичного ключа модели User
                'int', 'integer' => 'bigInteger',
                'string', 'uuid' => 'uuid',

                default => 'text',
            },
        ],
        'drivers' => (array)(explode(',', env('MODEL_LOG_DRIVERS', ModelLogDriverEnum::Database->value))),
        'file' => (string)env('MODEL_LOG_FILE', storage_path('logs/model.log')),
        'cleanup_days' => (int)env('MODEL_LOG_CLEANUP_DAYS', 7),
        'exclude' => (array)(Helper::envGet('MODEL_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * RouteLog. Логирование роутов
     */
    'route_log' => [
        'enabled' => (bool)env('ROUTE_LOG_ENABLED', false),
        'queue' => (string)env('ROUTE_LOG_QUEUE', 'default'),
        'connection' => (string)env('ROUTE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('ROUTE_LOG_TABLE', 'helper_route_logs'),
        'model' => RouteLog::class,
        'exclude' => (array)(Helper::envGet('ROUTE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueueLog. Логирование задач
     */
    'queue_log' => [
        'enabled' => (bool)env('QUEUE_LOG_ENABLED', false),
        'queue' => (string)env('QUEUE_LOG_QUEUE', 'default'),
        'connection' => (string)env('QUEUE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('QUEUE_LOG_TABLE', 'helper_route_logs'),
        'model' => QueueLog::class,
        'cleanup_days' => (int)env('QUEUE_LOG_CLEANUP_DAYS', 7),
        'store_on_start' => (bool)env('QUEUE_LOG_STORE_ON_START', false),
        'exclude' => (array)(Helper::envGet('QUEUE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * TelegramLog. Логирование в телеграм
     */
    'telegram_log' => [
        // Вкл/Выкл отправки в телеграм
        'enabled' => (bool)env('TELEGRAM_LOG_ENABLED', true),
        'queue' => (string)env('TELEGRAM_LOG_QUEUE', 'default'),

        // Токен бота
        'token' => (string)env('TELEGRAM_LOG_TOKEN'),

        // Настройка отправки логов информации
        'info' => [
            // Вкл/Выкл отправки информации
            'enabled' => (bool)env('TELEGRAM_INFO_ENABLED', true),
            // Telegram chat id для информации
            'chat_id' => (string)env('TELEGRAM_INFO_CHAT_ID'),
            // Токен бота для информации
            'token' => (string)env('TELEGRAM_INFO_TOKEN', env('TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('TELEGRAM_INFO_CACHE_TTL', '0 seconds'),
            'exclude' => (array)(Helper::envGet('TELEGRAM_INFO_EXCLUDE', base_path('.env')) ?? []),
        ],

        // Настройка отправки логов ошибок
        'error' => [
            // Вкл/Выкл отправки ошибок
            'enabled' => (bool)env('TELEGRAM_ERROR_ENABLED', true),
            // Telegram chat id для ошибок
            'chat_id' => (string)env('TELEGRAM_ERROR_CHAT_ID'),
            // Токен бота для ошибок
            'token' => (string)env('TELEGRAM_ERROR_TOKEN', env('TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('TELEGRAM_ERROR_CACHE_TTL', '5 minutes'),
            'exclude' => (array)(Helper::envGet('TELEGRAM_ERROR_EXCLUDE', base_path('.env')) ?? []),
        ],

        // Настройка отправки логов предупреждений
        'warning' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('TELEGRAM_WARNING_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => (string)env('TELEGRAM_WARNING_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => (string)env('TELEGRAM_WARNING_TOKEN', env('TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('TELEGRAM_WARNING_CACHE_TTL', '5 seconds'),
            'exclude' => (array)(Helper::envGet('TELEGRAM_WARNING_EXCLUDE', base_path('.env')) ?? []),
        ],

        // Настройка отправки логов отладки
        'debug' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('TELEGRAM_DEBUG_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => (string)env('TELEGRAM_DEBUG_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => (string)env('TELEGRAM_DEBUG_TOKEN', env('TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('TELEGRAM_DEBUG_CACHE_TTL', '5 seconds'),
            'exclude' => (array)(Helper::envGet('TELEGRAM_DEBUG_EXCLUDE', base_path('.env')) ?? []),
        ],
    ],


    'http' => [
        'smsRu' => [
            'enabled' => (bool)env('HTTP_SMSRU_ENABLED', false),
            'url' => (string)env('HTTP_SMSRU_URL', 'https://sms.ru'),
            'api_key' => (string)env('HTTP_SMSRU_API_KEY'),
            'from' => (string)env('HTTP_SMSRU_FROM'),
            'to' => (string)env('HTTP_SMSRU_TO'),
            'send_ip_address' => (bool)env('HTTP_SMSRU_SEND_IP_ADDRESS', false),
        ],

        'mangoOfficeRu' => [
            'enabled' => (bool)env('HTTP_MANGOOFFICERU_ENABLED', false),
            'url' => (string)env('HTTP_MANGOOFFICERU_URL', 'https://app.mango-office.ru/vpbx/'),
            'api_key' => (string)env('HTTP_MANGOOFFICERU_API_KEY', ''),
            'api_salt' => (string)env('HTTP_MANGOOFFICERU_API_SALT', ''),
            'webhook_token' => (string)env('HTTP_MANGOOFFICERU_WEBHOOK_TOKEN', 'mango_token'),
        ],

        'devlineRu' => [
            'enabled' => (bool)env('HTTP_DEVLINERU_ENABLED', false),
            'url' => [
                'http' => (string)env('HTTP_DEVLINERU_HTTP_URL', 'http://btAAAAA.loc.devline.tv:XXXX'),
                'rtsp' => (string)env('HTTP_DEVLINERU_RTSP_URL', 'rtsp://btAAAAA.loc.devline.tv:YYYY'),
            ],
            'timeout' => (int)env('HTTP_DEVLINERU_TIMEOUT', 10),
            'authorization' => (string)env('HTTP_DEVLINERU_AUTHORIZATION', ''),
        ],

        'rtspMe' => [
            'enabled' => (bool)env('HTTP_RTSPME_ENABLED', false),
            'url' => (string)env('HTTP_RTSPME_URL', 'https://rtsp.me'),
            'timeout' => (int)env('HTTP_RTSPME_TIMEOUT', 10),
            'auth' => [
                'email' => (string)env('HTTP_RTSPME_EMAIL'),
                'password' => (string)env('HTTP_RTSPME_PASSWORD'),
            ],
            'embed_url' => 'https://rtsp.me/embed/{rtspme_id}/',
        ],

        'fcmGoogleApisCom' => [
            'enabled' => (bool)env('HTTP_FCMGOOGLEAPISCOM_ENABLED', false),
            'url' => (string)env('HTTP_FCMGOOGLEAPISCOM_URL', 'https://fcm.googleapis.com/v1/'),
            'firebase_credentials' => (string)env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_CREDENTIALS', ''),
            'project_id' => (string)env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_PROJECT', ''),
            'timeout' => (int)env('HTTP_FCMGOOGLEAPISCOM_TIMEOUT', 30),
        ],

        'telegramOrg' => [
            'enabled' => (bool)env('HTTP_TELEGRAMORG_ENABLED', true),
            'url' => (string)env('HTTP_TELEGRAMORG_URL', 'https://api.telegram.org/'),
            'timeout' => (int)env('HTTP_TELEGRAMORG_TIMEOUT', 10),
        ],
    ],


    /**
     * QueryLog. Логирование query запросов
     */
    'query_log' => [
        'enabled' => (bool)env('QUERY_LOG_ENABLED', false),
        'queue' => (string)env('QUERY_LOG_QUEUE', 'default'),
        'connection' => (string)env('QUERY_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('QUERY_LOG_TABLE', 'helper_query_logs'),
        'model' => QueryLog::class,
        'cleanup_days' => (int)env('QUERY_LOG_CLEANUP_DAYS', 7),
        'store_on_start' => (bool)env('QUERY_LOG_STORE_ON_START', false),
        'exclude' => (array)(Helper::envGet('QUERY_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueryCache. Кеширование query запросов
     */
    'query_cache' => [
        'enabled' => (bool)env('QUERY_CACHE_ENABLED', true),
        'driver' => (string)env('QUERY_CACHE_DRIVER'),
        'ttl' => Helper::castToInt(env('QUERY_CACHE_TTL', 3600)),
        'exclude' => (array)(Helper::envGet('QUERY_CACHE_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * ViewLog. Логирование рендеринга blade шаблонов
     */
    'view_log' => [
        'enabled' => (bool)env('VIEW_LOG_ENABLED', false),
        'queue' => (string)env('VIEW_LOG_QUEUE', 'default'),
        'connection' => (string)env('VIEW_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        'table' => (string)env('VIEW_LOG_TABLE', 'helper_view_logs'),
        'model' => ViewLog::class,
        'cleanup_days' => (int)env('VIEW_LOG_CLEANUP_DAYS', 7),
        'store_on_start' => (bool)env('VIEW_LOG_STORE_ON_START', false),
        'exclude' => (array)(Helper::envGet('VIEW_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * ViewCache. Кеширование рендеринга blade шаблонов
     */
    'view_cache' => [
        'enabled' => (bool)env('VIEW_CACHE_ENABLED', true),
        'driver' => (string)env('VIEW_CACHE_DRIVER'),
        'ttl' => Helper::castToInt(env('VIEW_CACHE_TTL', 3600)),
        'exclude' => (array)(Helper::envGet('VIEW_CACHE_EXCLUDE', base_path('.env')) ?? []),
    ],
];
