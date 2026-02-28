<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Defaults\DefaultTest;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Dto\HttpCacheDto;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Сервис пакета laravel-helper
 */
class LaravelHelperService extends DefaultService
{
    /**
     * Возвращает конфиг по типу лога
     *
     * @param ConfigEnum $configType
     * @param string|null $configName
     * @param mixed|null $default
     * @return mixed
     */
    public function config(ConfigEnum $configType, ?string $configName = null, mixed $default = null): mixed
    {
        if (
            ($configName === 'enabled' || Hlp::stringEnds($configName, '.enabled'))
            && !(bool)config('laravel-helper.enabled', true)
        ) {
            return false;
        }

        return config("laravel-helper.{$configType->value}" . ($configName ? ".{$configName}" : ''), $default);
    }


    /**
     * Возвращает название соединения БД к таблице хелпера
     *
     * @param ConfigEnum $config
     * @return string
     */
    public function getConnection(ConfigEnum $config): string
    {
        return isTesting() ? DefaultTest::ENV : $this->config($config, 'connection');
    }


    /**
     * Возвращает название таблицы хелпера
     *
     * @param ConfigEnum $config
     * @param string $suffix
     * @return string
     */
    public function getTable(ConfigEnum $config, string $suffix = ''): string
    {
        return $this->config($config, Hlp::stringConcat('_', 'table', $suffix));
    }


    /**
     * Проверяет параметры конфига laravel-helper
     *
     * @return void
     */
    public function checkConfig(): void
    {
        $config = Hlp::arrayDot((array)config('laravel-helper') ?? []);

        !(
            ($config[$param = ConfigEnum::ConsoleLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::ConsoleLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::ConsoleLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::ConsoleLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::ConsoleLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::HttpLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::HttpLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::HttpLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::HttpLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::HttpLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::MailLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::MailLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::MailLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::MailLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::MailLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::ModelLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'cleanup_days'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'drivers'] ?? null)

            && ($config[$param = ConfigEnum::ProfilerLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::ProfilerLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::ProfilerLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::ProfilerLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::ProfilerLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::RouteLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::RouteLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::RouteLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::RouteLog->value . 'model'] ?? null)

            && ($config[$param = ConfigEnum::QueryLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::QueryLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::QueryLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::QueryLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::QueryLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::QueueLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::QueueLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::QueueLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::QueueLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::QueueLog->value . 'cleanup_days'] ?? null)

            && ($config[$param = ConfigEnum::TelegramLog->value . 'queue'] ?? null)

            && ($config[$param = ConfigEnum::TelegramBot->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::TelegramBot->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::TelegramBot->value . 'table_chat'] ?? null)
            && ($config[$param = ConfigEnum::TelegramBot->value . 'table_user'] ?? null)
            && ($config[$param = ConfigEnum::TelegramBot->value . 'table_message'] ?? null)
            && ($config[$param = ConfigEnum::TelegramBot->value . 'table_variable'] ?? null)

        ) ?? throw new WithoutTelegramException("Не указан параметр в конфиге: laravel-helper.{$param}");
    }


    /**
     * Проверяет массив dto на совпадение с массивом исключения laravel-helper.*.exclude
     * Возвращает true, если совпадения найдены
     *
     * @param string $configKey
     * @param array $data
     * @return bool
     */
    public function notFoundConfigExclude(string $configKey, Dto $dto): bool
    {
        $data = $dto->serializeKeys(true)->toArray();

        if ($exclude = config($configKey)) {
            $data = Hlp::arrayDot($data);
            $dataCheck = [];

            foreach ($data as $key => $val) {
                $dataCheck[] = "{$key}={$val}";
            }

            return empty(Hlp::arraySearchValues($dataCheck, $exclude));
        }

        return true;
    }


    /**
     * Проверяет массив с названиями таблиц на совпадение с массивом игнорируемых таблиц
     *
     * @param array $tables
     * @return bool
     */
    public function isFoundIgnoreTables(array $tables = []): bool
    {
        return !$this->notFoundIgnoreTables($tables);
    }


    /**
     * Проверяет массив с названиями таблиц на не совпадение с массивом игнорируемых таблиц
     *
     * @param array $tables
     * @return bool
     */
    public function notFoundIgnoreTables(array $tables = []): bool
    {
        static $ignoreTables = null;

        $ignoreTables ??= [
            $this->config(ConfigEnum::ConsoleLog, 'table'),
            $this->config(ConfigEnum::HttpLog, 'table'),
            $this->config(ConfigEnum::MailLog, 'table'),
            $this->config(ConfigEnum::ModelLog, 'table'),
            $this->config(ConfigEnum::ProfilerLog, 'table'),
            $this->config(ConfigEnum::QueueLog, 'table'),
            $this->config(ConfigEnum::QueryLog, 'table'),
            $this->config(ConfigEnum::RouteLog, 'table'),
            $this->config(ConfigEnum::ViewLog, 'table'),
            // $this->config(ConfigEnum::TelegramBot, 'table_chat'),
            // $this->config(ConfigEnum::TelegramBot, 'table_user'),
            // $this->config(ConfigEnum::TelegramBot, 'table_message'),
            config('cache.stores.database.table', 'cache'),
            'pg_catalog.*',
            'pg_attrdef',
            'information_schema.*',
            'table_schema.*',
            'telescope_*',
            'migrations',
        ];

        return empty(Hlp::arraySearchValues($tables, $ignoreTables));
    }


    /**
     * Проверяет на возможность отправки Dto в очередь для обработки
     *
     * @return bool
     */
    public function canDispatch(Dto $dto): bool
    {
        switch ($dto::class) {

            case ConsoleLogDto::class:
                $config = ConfigEnum::ConsoleLog;
                /** @var ConsoleLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case HttpCacheDto::class:
                $config = ConfigEnum::HttpCache;
                /** @var HttpCacheDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case HttpLogDto::class:
                $config = ConfigEnum::HttpLog;
                /** @var HttpLogDto $dto */
                $type = $dto->type->value;
                $can = $this->config($config, 'enabled')
                    && $this->config($config, "{$type}.enabled")
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.{$type}.exclude", $dto)
                    && (!in_array($dto->method, [HttpLogMethodEnum::Head->value, HttpLogMethodEnum::Options->value]))
                ;
                break;

            case MailLogDto::class:
                $config = ConfigEnum::MailLog;
                /** @var MailLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case ModelLogDto::class:
                $config = ConfigEnum::ModelLog;
                /** @var ModelLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case ProfilerLogDto::class:
                $config = ConfigEnum::ProfilerLog;
                /** @var ProfilerLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case QueryLogDto::class:
                $config = ConfigEnum::QueryLog;
                /** @var QueryLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                    && $this->notFoundIgnoreTables($dto->info['tables'] ?? [])
                    && !Hlp::arraySearchValues($dto->info['tables'] ?? [], [$this->config($config, 'table')])
                ;
                break;

            case QueueLogDto::class:
                $config = ConfigEnum::QueueLog;
                /** @var QueueLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                    && (($dto->info['class'] ?? null) !== QueueLogJob::class)
                ;
                break;

            case RouteLogDto::class:
                $config = ConfigEnum::RouteLog;
                /** @var RouteLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case TelegramLogDto::class:
                $config = ConfigEnum::TelegramLog;
                /** @var TelegramLogDto $dto */
                $type = $dto->type;
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.{$type}.exclude", $dto)
                ;
                break;

            case ViewLogDto::class:
                $config = ConfigEnum::ViewLog;
                /** @var ViewLogDto $dto */
                $can = $this->config($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            default:
                switch (true) {
                    case $dto instanceof TelegramBotOutDto:
                        $config = ConfigEnum::TelegramBot;
                        /** @var TelegramBotOutDto $dto */
                        $can = $this->config($config, 'enabled')
                            && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                        ;
                        break;

                    default:
                        $can = true;
                }
        }

        return $can;
    }


    /**
     * Возвращает класс модели по названию таблицы
     *
     * @param string $table
     * @return string|null
     */
    public function getModelClassByTable(string $table): ?string
    {
        static $tables = [];

        if (isset($tables[$table])) {
            return $tables[$table];
        }

        $modelPath = app_path();

        foreach (File::allFiles($modelPath) as $file) {
            $class = $this->getClassFromFile($file);

            if (
                $class && (
                    is_subclass_of($class, Model::class) || is_subclass_of($class, DefaultModel::class)
                )
            ) {
                $reflection = new ReflectionClass($class);
                if (!$reflection->isAbstract()) {
                    /** @var Model $model */
                    $model = new $class();
                    if ($model->getTable() === $table) {
                        return $class;
                    }
                }
            }
        }

        return $tables[$table] = match (true) {
            $table === $this->config(ConfigEnum::ConsoleLog, 'table') => ConsoleLog::class,
            $table === $this->config(ConfigEnum::HttpLog, 'table') => HttpLog::class,
            $table === $this->config(ConfigEnum::ModelLog, 'table') => ModelLog::class,
            $table === $this->config(ConfigEnum::ProfilerLog, 'table') => ProfilerLog::class,
            $table === $this->config(ConfigEnum::QueryLog, 'table') => QueryLog::class,
            $table === $this->config(ConfigEnum::QueueLog, 'table') => QueueLog::class,
            $table === $this->config(ConfigEnum::RouteLog, 'table') => RouteLog::class,
            $table === $this->config(ConfigEnum::ViewLog, 'table') => ViewLog::class,
            $table === $this->config(ConfigEnum::TelegramBot, 'table_chat') => TelegramBotChat::class,
            $table === $this->config(ConfigEnum::TelegramBot, 'table_user') => TelegramBotUser::class,
            $table === $this->config(ConfigEnum::TelegramBot, 'table_message') => TelegramBotMessage::class,

            default => null,
        };
    }


    /**
     * Возвращает полное имя класса с namespace из файла
     *
     * @param string $filePath
     * @return string|null
     */
    public function getClassFromFile(SplFileInfo|string $file): ?string
    {
        static $classes = [];

        !($file instanceof SplFileInfo) ?: $file = app_path($file->getRelativePathname());

        if (isset($classes[$file])) {
            return $classes[$file];
        }

        $fileData = File::exists($file) ? File::get($file) : '';

        if ($fileData && preg_match('/namespace\s+([^;]+);/', $fileData, $matches)) {
            $namespace = trim($matches[1] ?? '');

            if (
                $namespace
                && preg_match(
                    '/class\s+(\w+)\s*(?:extends\s+\w+)?\s*(?:implements\s+[\w\\\,\s]+)?\s*{?/',
                    $fileData,
                    $matches,
                )
            ) {
                $class = trim($matches[1] ?? '');
                $classWithNamespace = "{$namespace}\\$class";

                return $classes[$file] = ($class && class_exists($classWithNamespace)) ? $classWithNamespace : null;
            }
        }

        return $classes[$file] = null;
    }
}
