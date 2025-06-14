<?php

use Atlcom\Hlp;
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

// Название таблицы пользователей
$userTableName = (string)(new User())->getTable();
// Первичный ключ в таблице пользователей
$userPrimaryKeyName = (string)(new User())->getKeyName();
// Тип первичного ключа в таблице пользователей
$userPrimaryKeyType = (string)match ((new User())->getKeyType()) {
    'int', 'integer' => 'bigInteger',
    'string', 'uuid' => 'uuid',

    default => 'text',
};

return [
    /**
     * Application. Настройки пакета
     */
    'app' => [
        // Версия настроек пакета laravel-helper
        'version' => '1.00',
        // Флаг включения отладочной информации в response
        'debug' => (bool)env('APP_DEBUG', false),
        // Флаг включения отладочной информации в сообщение телеграм
        'debug_data' => (bool)env('APP_DEBUG_DATA', false),
        // Флаг включения вывода трассировки ошибки
        'debug_trace' => (bool)env('APP_DEBUG_TRACE', false),
        // Флаг включения вывода vendor классов в трассировку
        'debug_trace_vendor' => (bool)env('APP_DEBUG_TRACE_VENDOR', false),
    ],


    /**
     * Testing. Авто-тестирование
     */
    'testing' => [
        'email' => 'test@test.ru',
    ],

    /**
     * Optimize. Настройки при выполнении команды оптимизации
     */
    'optimize' => [
        // Очистка таблиц
        'cleanup' => [
            // Флаг включения
            'enabled' => (bool)env('OPTIMIZE_CLEANUP_ALL_ENABLED', true),
        ],
    ],


    /**
     * Macro. Включение макросов хелпера
     */
    'macros' => [
        // Макросы конструктора query запросов
        'builder' => [
            // Флаг включения макросов
            'enabled' => (bool)env('BUILDER_MACROS_ENABLED', true),
        ],
        // Макросы хелпера Str
        'str' => [
            // Флаг включения макросов
            'enabled' => (bool)env('STR_MACROS_ENABLED', true),
        ],
        // Макросы фасада Http
        'http' => [
            // Флаг включения макросов
            'enabled' => (bool)env('HTTP_MACROS_ENABLED', true),
        ],
    ],


    /**
     * ConsoleLog. Логирование консольных команд
     */
    'console_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('CONSOLE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('CONSOLE_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('CONSOLE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('CONSOLE_LOG_TABLE', 'helper_route_logs'),
        // Класс модели логов
        'model' => ConsoleLog::class,
        // Количество дней хранения логов
        'cleanup_days' => (int)env('CONSOLE_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('CONSOLE_LOG_STORE_ON_START', false),
        // Интервал записи логов для длительных операций
        'store_interval_seconds' => (int)env('CONSOLE_LOG_STORE_INTERVAL_SECONDS', 3),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('CONSOLE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * HttpLog. Логирование http запросов
     */
    'http_log' => [
        // Название очереди для логов
        'queue' => (string)env('HTTP_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('HTTP_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HTTP_LOG_TABLE', 'helper_http_logs'),
        // Класс модели логов
        'model' => HttpLog::class,
        // Связь с таблицей пользователей
        'user' => [
            // Название таблицы модели User
            'table_name' => (string)$userTableName,
            // Название первичного ключа модели User
            'primary_key' => (string)$userPrimaryKeyName,
            // Тип первичного ключа модели User
            'primary_type' => (string)$userPrimaryKeyType,
        ],
        // Флаг включения записи логов только после получения ответа на запрос
        'only_response' => (bool)env('HTTP_LOG_ONLY_RESPONSE', true),
        // Входящие запросы
        'in' => [
            // Флаг включения логов
            'enabled' => (bool)env('HTTP_LOG_IN_ENABLED', env('HTTP_LOG_ENABLED', false)),
            // Исключения логов, например ['name' => '...']
            'exclude' => (array)(Hlp::envGet('HTTP_LOG_IN_EXCLUDE', base_path('.env')) ?? []),
            // Флаг включения middleware глобально
            'global' => (bool)env('HTTP_LOG_IN_GLOBAL', false),
        ],
        // Исходящие запросы
        'out' => [
            // Флаг включения логов
            'enabled' => (bool)env('HTTP_LOG_OUT_ENABLED', env('HTTP_LOG_ENABLED', false)),
            // Исключения логов, например ['name' => '...']
            'exclude' => (array)(Hlp::envGet('HTTP_LOG_OUT_EXCLUDE', base_path('.env')) ?? []),
        ],
        // Количество дней хранения логов
        'cleanup_days' => (int)env('HTTP_LOG_CLEANUP_DAYS', 7),

    ],


    /**
     * ModelLog. Логирование моделей
     */
    'model_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('MODEL_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('MODEL_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('MODEL_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('MODEL_LOG_TABLE', 'helper_model_logs'),
        // Класс модели логов
        'model' => ModelLog::class,
        // Связь с таблицей пользователей
        'user' => [
            // Название таблицы модели User
            'table_name' => (string)$userTableName,
            // Название первичного ключа модели User
            'primary_key' => (string)$userPrimaryKeyName,
            // Тип первичного ключа модели User
            'primary_type' => (string)$userPrimaryKeyType,
        ],
        // Название драйвера логов
        'drivers' => (array)(explode(',', env('MODEL_LOG_DRIVERS', ModelLogDriverEnum::Database->value))),
        // Название файла для драйвера File
        'file' => (string)env('MODEL_LOG_FILE', storage_path('logs/model.log')),
        // Количество дней хранения логов
        'cleanup_days' => (int)env('MODEL_LOG_CLEANUP_DAYS', 7),
        // Исключения логов, например ['type' => '...']
        'exclude' => (array)(Hlp::envGet('MODEL_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * RouteLog. Логирование роутов
     */
    'route_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('ROUTE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('ROUTE_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('ROUTE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('ROUTE_LOG_TABLE', 'helper_route_logs'),
        // Класс модели логов
        'model' => RouteLog::class,
        // Исключения логов, например ['uri' => '...']
        'exclude' => (array)(Hlp::envGet('ROUTE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueryCache. Кеширование query запросов
     */
    'query_cache' => [
        // Флаг включения кеша
        'enabled' => (bool)env('QUERY_CACHE_ENABLED', true),
        // Название драйвера кеша
        'driver' => (string)env('QUERY_CACHE_DRIVER'),
        // Срок жизни ключа кеша по умолчанию
        'ttl' => Hlp::castToInt(env('QUERY_CACHE_TTL', 3600)),
        // Исключения кеша, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('QUERY_CACHE_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueryLog. Логирование query запросов
     */
    'query_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('QUERY_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('QUERY_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('QUERY_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('QUERY_LOG_TABLE', 'helper_query_logs'),
        // Класс модели логов
        'model' => QueryLog::class,
        // Связь с таблицей пользователей
        'user' => [
            // Название таблицы модели User
            'table_name' => (string)$userTableName,
            // Название первичного ключа модели User
            'primary_key' => (string)$userPrimaryKeyName,
            // Тип первичного ключа модели User
            'primary_type' => (string)$userPrimaryKeyType,
        ],
        // Количество дней хранения логов
        'cleanup_days' => (int)env('QUERY_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('QUERY_LOG_STORE_ON_START', false),
        // Исключения логов, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('QUERY_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueueLog. Логирование очередей
     */
    'queue_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('QUEUE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('QUEUE_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('QUEUE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('QUEUE_LOG_TABLE', 'helper_route_logs'),
        // Класс модели логов
        'model' => QueueLog::class,
        // Связь с таблицей пользователей
        'user' => [
            // Название таблицы модели User
            'table_name' => (string)$userTableName,
            // Название первичного ключа модели User
            'primary_key' => (string)$userPrimaryKeyName,
            // Тип первичного ключа модели User
            'primary_type' => (string)$userPrimaryKeyType,
        ],
        // Количество дней хранения логов
        'cleanup_days' => (int)env('QUEUE_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('QUEUE_LOG_STORE_ON_START', false),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('QUEUE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * TelegramLog. Логирование в телеграм
     */
    'telegram_log' => [
        // Вкл/Выкл отправки в телеграм
        'enabled' => (bool)env('TELEGRAM_LOG_ENABLED', true),
        // Название очереди для логов
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
            'exclude' => (array)(Hlp::envGet('TELEGRAM_INFO_EXCLUDE', base_path('.env')) ?? []),
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
            'exclude' => (array)(Hlp::envGet('TELEGRAM_ERROR_EXCLUDE', base_path('.env')) ?? []),
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
            'exclude' => (array)(Hlp::envGet('TELEGRAM_WARNING_EXCLUDE', base_path('.env')) ?? []),
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
            'exclude' => (array)(Hlp::envGet('TELEGRAM_DEBUG_EXCLUDE', base_path('.env')) ?? []),
        ],
    ],

    /**
     * Http Macro. Макросы исходящих http запросов через фасад Http
     */
    'http' => [
        // Сервис sms.ru
        'smsRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_SMSRU_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HTTP_SMSRU_URL', 'https://sms.ru'),
            // Ключ api
            'api_key' => (string)env('HTTP_SMSRU_API_KEY'),
            // Номер отправителя сообщений
            'from' => (string)env('HTTP_SMSRU_FROM'),
            // Номер получателя сообщений
            'to' => (string)env('HTTP_SMSRU_TO'),
            // Флаг включения отправки ip адреса клиента
            'send_ip_address' => (bool)env('HTTP_SMSRU_SEND_IP_ADDRESS', false),
        ],
        // Сервис mango-office.ru
        'mangoOfficeRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_MANGOOFFICERU_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HTTP_MANGOOFFICERU_URL', 'https://app.mango-office.ru/vpbx/'),
            // Ключ api
            'api_key' => (string)env('HTTP_MANGOOFFICERU_API_KEY', ''),
            // Дополнительная соль для формирования подписи
            'api_salt' => (string)env('HTTP_MANGOOFFICERU_API_SALT', ''),
            // Токен вебхука для входящих запросов от сервиса
            'webhook_token' => (string)env('HTTP_MANGOOFFICERU_WEBHOOK_TOKEN', 'mango_token'),
        ],
        // Сервис mango-devline.ru
        'devlineRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_DEVLINERU_ENABLED', false),
            // Url адрес для запросов api
            'url' => [
                // Url адрес для формирования http ссылки видео-потока камеры
                'http' => (string)env('HTTP_DEVLINERU_HTTP_URL', 'http://btAAAAA.loc.devline.tv:XXXX'),
                // Url адрес для формирования rtsp ссылки видео-потока
                'rtsp' => (string)env('HTTP_DEVLINERU_RTSP_URL', 'rtsp://btAAAAA.loc.devline.tv:YYYY'),
            ],
            'timeout' => (int)env('HTTP_DEVLINERU_TIMEOUT', 10),

            'authorization' => (string)env('HTTP_DEVLINERU_AUTHORIZATION', ''),
        ],
        // Сервис rtsp.me
        'rtspMe' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_RTSPME_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HTTP_RTSPME_URL', 'https://rtsp.me'),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HTTP_RTSPME_TIMEOUT', 10),
            // Креды авторизации api
            'auth' => [
                // Адрес электронной почты
                'email' => (string)env('HTTP_RTSPME_EMAIL'),
                // Пароль
                'password' => (string)env('HTTP_RTSPME_PASSWORD'),
            ],
            'embed_url' => 'https://rtsp.me/embed/{rtspme_id}/',
        ],
        // Сервис FCM Google API
        'fcmGoogleApisCom' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_FCMGOOGLEAPISCOM_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HTTP_FCMGOOGLEAPISCOM_URL', 'https://fcm.googleapis.com/v1/'),
            // Креды авторизации api
            'firebase_credentials' => (string)env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_CREDENTIALS', ''),
            // Идентификатор проекта fcm
            'project_id' => (string)env('HTTP_FCMGOOGLEAPISCOM_FIREBASE_PROJECT', ''),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HTTP_FCMGOOGLEAPISCOM_TIMEOUT', 30),
        ],
        // Сервис Telegram API
        'telegramOrg' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HTTP_TELEGRAMORG_ENABLED', true),
            // Url адрес для запросов api
            'url' => (string)env('HTTP_TELEGRAMORG_URL', 'https://api.telegram.org/'),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HTTP_TELEGRAMORG_TIMEOUT', 10),
        ],
    ],


    /**
     * ViewLog. Логирование рендеринга blade шаблонов
     */
    'view_log' => [
        // Флаг включения логов
        'enabled' => (bool)env('VIEW_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('VIEW_LOG_QUEUE', 'default'),
        // Название соединения для записи логов
        'connection' => (string)env('VIEW_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('VIEW_LOG_TABLE', 'helper_view_logs'),
        // Класс модели логов
        'model' => ViewLog::class,
        // Связь с таблицей пользователей
        'user' => [
            // Название таблицы модели User
            'table_name' => (string)$userTableName,
            // Название первичного ключа модели User
            'primary_key' => (string)$userPrimaryKeyName,
            // Тип первичного ключа модели User
            'primary_type' => (string)$userPrimaryKeyType,
        ],
        // Количество дней хранения логов
        'cleanup_days' => (int)env('VIEW_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('VIEW_LOG_STORE_ON_START', false),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('VIEW_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * ViewCache. Кеширование рендеринга blade шаблонов
     */
    'view_cache' => [
        // Флаг включения кеша
        'enabled' => (bool)env('VIEW_CACHE_ENABLED', true),
        // Название драйвера кеша
        'driver' => (string)env('VIEW_CACHE_DRIVER'),
        // Срок жизни ключа кеша по умолчанию
        'ttl' => Hlp::castToInt(env('VIEW_CACHE_TTL', 3600)),
        // Исключения кеша, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('VIEW_CACHE_EXCLUDE', base_path('.env')) ?? []),
    ],
];
