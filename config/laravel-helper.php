<?php

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
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
$userClass = (string)env('HELPER_USER_CLASS', config('auth.providers.users.model', User::class));
$user = new $userClass();
$userTableName = (string)$user->getTable();
// Первичный ключ в таблице пользователей
$userPrimaryKeyName = (string)$user->getKeyName();
// Тип первичного ключа в таблице пользователей
$userPrimaryKeyType = (string)match ($user->getKeyType()) {
    'int', 'integer' => 'bigInteger',
    'string', 'uuid' => 'uuid',

    default => 'text',
};

return [
    /**
     * Application. Настройки пакета
     */
    ConfigEnum::App->value => [
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
        // Класс пользователя
        'user' => $userClass,
    ],


    /**
     * Optimize. Настройки при выполнении команды оптимизации
     */
    ConfigEnum::Optimize->value => [
        // Очистка таблиц
        'log_cleanup' => [
            // Флаг включения
            'enabled' => (bool)env('HELPER_OPTIMIZE_LOG_CLEANUP_ENABLED', true),
        ],
        // Очистка кеша
        'cache_clear' => [
            // Флаг включения
            'enabled' => (bool)env('HELPER_OPTIMIZE_CACHE_CLEAR_ENABLED', true),
        ],
    ],


    /**
     * Macro. Включение макросов хелпера
     */
    ConfigEnum::Macros->value => [
        // Макросы конструктора query запросов
        'builder' => [
            // Флаг включения макросов
            'enabled' => (bool)env('HELPER_BUILDER_MACROS_ENABLED', true),
        ],
        // Макросы хелпера Str
        'str' => [
            // Флаг включения макросов
            'enabled' => (bool)env('HELPER_STR_MACROS_ENABLED', true),
        ],
        // Макросы фасада Http
        'http' => [
            // Флаг включения макросов
            'enabled' => (bool)env('HELPER_HTTP_MACROS_ENABLED', true),
        ],
    ],


    /**
     * ConsoleLog. Логирование консольных команд
     */
    ConfigEnum::ConsoleLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_CONSOLE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_CONSOLE_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_CONSOLE_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_CONSOLE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_CONSOLE_LOG_TABLE', 'helper_console_logs'),
        // Класс модели логов
        'model' => ConsoleLog::class,
        // Количество дней хранения логов
        'cleanup_days' => (int)env('HELPER_CONSOLE_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('HELPER_CONSOLE_LOG_STORE_ON_START', false),
        // Интервал записи логов для длительных операций
        'store_interval_seconds' => (int)env('HELPER_CONSOLE_LOG_STORE_INTERVAL_SECONDS', 3),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_CONSOLE_LOG_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения логирования всех консольных команд
        'global' => (bool)env('HELPER_CONSOLE_LOG_GLOBAL', false),
    ],


    /**
     * HttpLog. Логирование http запросов
     */
    ConfigEnum::HttpLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_HTTP_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_HTTP_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_HTTP_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_HTTP_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_HTTP_LOG_TABLE', 'helper_http_logs'),
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
        'only_response' => (bool)env('HELPER_HTTP_LOG_ONLY_RESPONSE', true),
        // Входящие запросы
        'in' => [
            // Флаг включения логов
            'enabled' => (bool)env('HELPER_HTTP_LOG_IN_ENABLED', env('HELPER_HTTP_LOG_ENABLED', false)),
            // Исключения логов, например ['name' => '...']
            'exclude' => (array)(Hlp::envGet('HELPER_HTTP_LOG_IN_EXCLUDE', base_path('.env')) ?? []),
            // Флаг включения логирования всех входящих запросов (HttpLogMiddleware глобально)
            'global' => (bool)env('HELPER_HTTP_LOG_IN_GLOBAL', false),
        ],
        // Исходящие запросы
        'out' => [
            // Флаг включения логов
            'enabled' => (bool)env('HELPER_HTTP_LOG_OUT_ENABLED', env('HELPER_HTTP_LOG_ENABLED', false)),
            // Исключения логов, например ['name' => '...']
            'exclude' => (array)(Hlp::envGet('HELPER_HTTP_LOG_OUT_EXCLUDE', base_path('.env')) ?? []),
            // Флаг включения логирования всех исходящих запросов
            'global' => (bool)env('HELPER_HTTP_LOG_OUT_GLOBAL', true),
        ],
        // Количество дней хранения логов
        'cleanup_days' => (int)env('HELPER_HTTP_LOG_CLEANUP_DAYS', 7),
    ],


    /**
     * ModelLog. Логирование моделей
     */
    ConfigEnum::ModelLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_MODEL_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_MODEL_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_MODEL_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_MODEL_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_MODEL_LOG_TABLE', 'helper_model_logs'),
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
        'drivers' => (array)(explode(',', env('HELPER_MODEL_LOG_DRIVERS', ModelLogDriverEnum::Database->value))),
        // Название файла для драйвера File
        'file' => (string)env('HELPER_MODEL_LOG_FILE', storage_path('logs/model.log')),
        // Количество дней хранения логов
        'cleanup_days' => (int)env('HELPER_MODEL_LOG_CLEANUP_DAYS', 7),
        // Исключения логов, например ['type' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_MODEL_LOG_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения логирования всех моделей
        'global' => (bool)env('HELPER_MODEL_LOG_GLOBAL', false),
    ],


    /**
     * RouteLog. Логирование роутов
     */
    ConfigEnum::RouteLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_ROUTE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_ROUTE_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_ROUTE_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_ROUTE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_ROUTE_LOG_TABLE', 'helper_route_logs'),
        // Класс модели логов
        'model' => RouteLog::class,
        // Исключения логов, например ['uri' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_ROUTE_LOG_EXCLUDE', base_path('.env')) ?? []),
    ],


    /**
     * QueryCache. Кеширование query запросов
     */
    ConfigEnum::QueryCache->value => [
        // Флаг включения кеша
        'enabled' => (bool)env('HELPER_QUERY_CACHE_ENABLED', true),
        // Название драйвера кеша
        'driver' => (string)env('HELPER_QUERY_CACHE_DRIVER', env('CACHE_STORE', 'database')),
        // Название папки кеша для драйвера file
        'driver_file_path' => (string)env('HELPER_QUERY_CACHE_DRIVER_FILE_PATH', storage_path('framework/cache/query')),
        // Сжимать данные кеша
        'gzdeflate' => [
            'enabled' => (bool)env('HELPER_QUERY_CACHE_GZDEFLATE_ENABLED', true),
            'level' => (int)env('HELPER_QUERY_CACHE_GZDEFLATE_LEVEL', 9),
        ],
        // Срок жизни ключа кеша по умолчанию
        'ttl' => Hlp::castToInt(env('HELPER_QUERY_CACHE_TTL', 3600)),
        // Исключения кеша, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_QUERY_CACHE_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения кеширования всех query запросов
        'global' => (bool)env('HELPER_QUERY_CACHE_GLOBAL', false),
    ],


    /**
     * QueryLog. Логирование query запросов
     */
    ConfigEnum::QueryLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_QUERY_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_QUERY_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_QUERY_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_QUERY_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_QUERY_LOG_TABLE', 'helper_query_logs'),
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
        'cleanup_days' => (int)env('HELPER_QUERY_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('HELPER_QUERY_LOG_STORE_ON_START', false),
        // Исключения логов, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_QUERY_LOG_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения логирования всех query запросов
        'global' => (bool)env('HELPER_QUERY_LOG_GLOBAL', false),
    ],


    /**
     * QueueLog. Логирование очередей
     */
    ConfigEnum::QueueLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_QUEUE_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_QUEUE_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_QUEUE_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_QUEUE_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_QUEUE_LOG_TABLE', 'helper_queue_logs'),
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
        'cleanup_days' => (int)env('HELPER_QUEUE_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('HELPER_QUEUE_LOG_STORE_ON_START', false),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_QUEUE_LOG_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения логирования всех очередей
        'global' => (bool)env('HELPER_QUEUE_LOG_GLOBAL', false),
    ],


    /**
     * TelegramLog. Логирование в телеграм
     */
    ConfigEnum::TelegramLog->value => [
        // Вкл/Выкл отправки в телеграм
        'enabled' => (bool)env('HELPER_TELEGRAM_LOG_ENABLED', true),
        // Название очереди для логов
        'queue' => (string)env('HELPER_TELEGRAM_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_TELEGRAM_LOG_QUEUE_DISPATCH_SYNC'),
        // Токен бота
        'token' => (string)env('HELPER_TELEGRAM_LOG_TOKEN'),
        // Настройка отправки логов информации
        'info' => [
            // Вкл/Выкл отправки информации
            'enabled' => (bool)env('HELPER_TELEGRAM_INFO_ENABLED', true),
            // Telegram chat id для информации
            'chat_id' => (string)env('HELPER_TELEGRAM_INFO_CHAT_ID'),
            // Токен бота для информации
            'token' => (string)env('HELPER_TELEGRAM_INFO_TOKEN', env('HELPER_TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('HELPER_TELEGRAM_INFO_CACHE_TTL', '0 seconds'),
            'exclude' => (array)(Hlp::envGet('HELPER_TELEGRAM_INFO_EXCLUDE', base_path('.env')) ?? []),
        ],
        // Настройка отправки логов ошибок
        'error' => [
            // Вкл/Выкл отправки ошибок
            'enabled' => (bool)env('HELPER_TELEGRAM_ERROR_ENABLED', true),
            // Telegram chat id для ошибок
            'chat_id' => (string)env('HELPER_TELEGRAM_ERROR_CHAT_ID'),
            // Токен бота для ошибок
            'token' => (string)env('HELPER_TELEGRAM_ERROR_TOKEN', env('HELPER_TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('HELPER_TELEGRAM_ERROR_CACHE_TTL', '5 minutes'),
            'exclude' => (array)(Hlp::envGet('HELPER_TELEGRAM_ERROR_EXCLUDE', base_path('.env')) ?? []),
        ],
        // Настройка отправки логов предупреждений
        'warning' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('HELPER_TELEGRAM_WARNING_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => (string)env('HELPER_TELEGRAM_WARNING_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => (string)env('HELPER_TELEGRAM_WARNING_TOKEN', env('HELPER_TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('HELPER_TELEGRAM_WARNING_CACHE_TTL', '5 seconds'),
            'exclude' => (array)(Hlp::envGet('HELPER_TELEGRAM_WARNING_EXCLUDE', base_path('.env')) ?? []),
        ],
        // Настройка отправки логов отладки
        'debug' => [
            // Вкл/Выкл отправки предупреждений
            'enabled' => (bool)env('HELPER_TELEGRAM_DEBUG_ENABLED', true),
            // Telegram chat id для предупреждений
            'chat_id' => (string)env('HELPER_TELEGRAM_DEBUG_CHAT_ID'),
            // Токен бота для предупреждений
            'token' => (string)env('HELPER_TELEGRAM_DEBUG_TOKEN', env('HELPER_TELEGRAM_LOG_TOKEN')),
            // Кеш повторной отправки в группу чата
            'cache_ttl' => (string)env('HELPER_TELEGRAM_DEBUG_CACHE_TTL', '5 seconds'),
            'exclude' => (array)(Hlp::envGet('HELPER_TELEGRAM_DEBUG_EXCLUDE', base_path('.env')) ?? []),
        ],
    ],

    /**
     * Http Macro. Макросы исходящих http запросов через фасад Http
     */
    ConfigEnum::Http->value => [
        // Сервис localhost
        'localhost' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_LOCALHOST_ENABLED', true),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_LOCALHOST_URL', env('APP_URL', 'http://localhost:80')),
        ],
        // Сервис sms.ru
        'smsRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_SMSRU_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_SMSRU_URL', 'https://sms.ru'),
            // Ключ api
            'api_key' => (string)env('HELPER_HTTP_SMSRU_API_KEY'),
            // Номер отправителя сообщений
            'from' => (string)env('HELPER_HTTP_SMSRU_FROM'),
            // Номер получателя сообщений
            'to' => (string)env('HELPER_HTTP_SMSRU_TO'),
            // Флаг включения отправки ip адреса клиента
            'send_ip_address' => (bool)env('HELPER_HTTP_SMSRU_SEND_IP_ADDRESS', false),
        ],
        // Сервис mango-office.ru
        'mangoOfficeRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_MANGOOFFICERU_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_MANGOOFFICERU_URL', 'https://app.mango-office.ru/vpbx/'),
            // Ключ api
            'api_key' => (string)env('HELPER_HTTP_MANGOOFFICERU_API_KEY', ''),
            // Дополнительная соль для формирования подписи
            'api_salt' => (string)env('HELPER_HTTP_MANGOOFFICERU_API_SALT', ''),
            // Токен вебхука для входящих запросов от сервиса
            'webhook_token' => (string)env('HELPER_HTTP_MANGOOFFICERU_WEBHOOK_TOKEN', 'mango_token'),
        ],
        // Сервис mango-devline.ru
        'devlineRu' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_DEVLINERU_ENABLED', false),
            // Url адрес для запросов api
            'url' => [
                // Url адрес для формирования http ссылки видео-потока камеры
                'http' => (string)env('HELPER_HTTP_DEVLINERU_HTTP_URL', 'http://btAAAAA.loc.devline.tv:XXXX'),
                // Url адрес для формирования rtsp ссылки видео-потока
                'rtsp' => (string)env('HELPER_HTTP_DEVLINERU_RTSP_URL', 'rtsp://btAAAAA.loc.devline.tv:YYYY'),
            ],
            'timeout' => (int)env('HELPER_HTTP_DEVLINERU_TIMEOUT', 10),

            'authorization' => (string)env('HELPER_HTTP_DEVLINERU_AUTHORIZATION', ''),
        ],
        // Сервис rtsp.me
        'rtspMe' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_RTSPME_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_RTSPME_URL', 'https://rtsp.me'),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HELPER_HTTP_RTSPME_TIMEOUT', 10),
            // Креды авторизации api
            'auth' => [
                // Адрес электронной почты
                'email' => (string)env('HELPER_HTTP_RTSPME_EMAIL'),
                // Пароль
                'password' => (string)env('HELPER_HTTP_RTSPME_PASSWORD'),
            ],
            'embed_url' => 'https://rtsp.me/embed/{rtspme_id}/',
        ],
        // Сервис FCM Google API
        'fcmGoogleApisCom' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_FCMGOOGLEAPISCOM_ENABLED', false),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_FCMGOOGLEAPISCOM_URL', 'https://fcm.googleapis.com/v1/'),
            // Креды авторизации api
            'firebase_credentials' => (string)env('HELPER_HTTP_FCMGOOGLEAPISCOM_FIREBASE_CREDENTIALS', ''),
            // Идентификатор проекта fcm
            'project_id' => (string)env('HELPER_HTTP_FCMGOOGLEAPISCOM_FIREBASE_PROJECT', ''),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HELPER_HTTP_FCMGOOGLEAPISCOM_TIMEOUT', 30),
        ],
        // Сервис Telegram API
        'telegramOrg' => [
            // Флаг включения макроса
            'enabled' => (bool)env('HELPER_HTTP_TELEGRAMORG_ENABLED', true),
            // Url адрес для запросов api
            'url' => (string)env('HELPER_HTTP_TELEGRAMORG_URL', 'https://api.telegram.org/'),
            // Таймаут подключения api к сервису
            'timeout' => (int)env('HELPER_HTTP_TELEGRAMORG_TIMEOUT', 10),
        ],
    ],


    /**
     * ViewLog. Логирование рендеринга blade шаблонов
     */
    ConfigEnum::ViewLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_VIEW_LOG_ENABLED', false),
        // Название очереди для логов
        'queue' => (string)env('HELPER_VIEW_LOG_QUEUE', 'default'),
        // Запуск очереди синхронно
        'queue_dispatch_sync' => (bool)env('HELPER_VIEW_LOG_QUEUE_DISPATCH_SYNC'),
        // Название соединения для записи логов
        'connection' => (string)env('HELPER_VIEW_LOG_CONNECTION', env('DB_CONNECTION', 'sqlite')),
        // Название таблицы для записи логов
        'table' => (string)env('HELPER_VIEW_LOG_TABLE', 'helper_view_logs'),
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
        'cleanup_days' => (int)env('HELPER_VIEW_LOG_CLEANUP_DAYS', 7),
        // Флаг включения записи логов перед запуском
        'store_on_start' => (bool)env('HELPER_VIEW_LOG_STORE_ON_START', false),
        // Исключения логов, например ['name' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_VIEW_LOG_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения логирования рендеринга всех blade шаблонов
        'global' => (bool)env('HELPER_VIEW_LOG_GLOBAL', false),
    ],


    /**
     * ViewCache. Кеширование рендеринга blade шаблонов
     */
    ConfigEnum::ViewCache->value => [
        // Флаг включения кеша
        'enabled' => (bool)env('HELPER_VIEW_CACHE_ENABLED', true),
        // Название драйвера кеша
        'driver' => (string)env('HELPER_VIEW_CACHE_DRIVER'),
        // Сжимать данные кеша
        'gzdeflate' => [
            'enabled' => (bool)env('HELPER_VIEW_CACHE_GZDEFLATE_ENABLED', true),
            'level' => (int)env('HELPER_VIEW_CACHE_GZDEFLATE_LEVEL', 9),
        ],
        // Срок жизни ключа кеша по умолчанию
        'ttl' => Hlp::castToInt(env('HELPER_VIEW_CACHE_TTL', 3600)),
        // Исключения кеша, например ['key' => '...']
        'exclude' => (array)(Hlp::envGet('HELPER_VIEW_CACHE_EXCLUDE', base_path('.env')) ?? []),
        // Флаг включения кеширования рендеринга всех blade шаблонов
        'global' => (bool)env('HELPER_VIEW_CACHE_GLOBAL', false),
    ],

    /**
     * Testing. Авто-тестирование
     */
    ConfigEnum::TestingLog->value => [
        // Флаг включения логов
        'enabled' => (bool)env('HELPER_TESTING_LOG_ENABLED', true),

        'helper_logs' => [
            // Флаг включения логов
            'enabled' => (bool)env('HELPER_TESTING_HELPER_LOGS_ENABLED', true),
        ],

        // Пользователь для тестов
        'user' => [
            'email' => (string)env('HELPER_TESTING_LOG_USER_EMAIL', 'testing@test.ru'),
        ],
        // Настройка тестовой базы данных
        'database' => [
            'name' => (string)env('HELPER_TESTING_LOG_DATABASE_NAME', 'testing'),
            'fresh' => (bool)env('HELPER_TESTING_LOG_DATABASE_FRESH_ENABLED', false),
            'seed' => (bool)env('HELPER_TESTING_LOG_DATABASE_SEED_ENABLED', false),
        ],
    ],
];