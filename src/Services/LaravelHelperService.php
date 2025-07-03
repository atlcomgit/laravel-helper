<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Defaults\DefaultTest;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
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
     * Возвращает название соединения БД к таблице лога
     *
     * @param ConfigEnum $config
     * @return string
     */
    public static function getConnection(ConfigEnum $config): string
    {
        return isTesting() ? DefaultTest::ENV : lhConfig($config, 'connection');
    }


    /**
     * Возвращает название таблицы лога
     *
     * @param ConfigEnum $config
     * @return string
     */
    public static function getTable(ConfigEnum $config): string
    {
        return lhConfig($config, 'table');
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

            && ($config[$param = ConfigEnum::ModelLog->value . 'queue'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'connection'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'table'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'model'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'cleanup_days'] ?? null)
            && ($config[$param = ConfigEnum::ModelLog->value . 'drivers'] ?? null)

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
            lhConfig(ConfigEnum::ConsoleLog, 'table'),
            lhConfig(ConfigEnum::HttpLog, 'table'),
            lhConfig(ConfigEnum::ModelLog, 'table'),
            lhConfig(ConfigEnum::QueueLog, 'table'),
            lhConfig(ConfigEnum::QueryLog, 'table'),
            lhConfig(ConfigEnum::RouteLog, 'table'),
            lhConfig(ConfigEnum::ViewLog, 'table'),
            config('cache.stores.database.table', 'cache'),
            'pg_catalog.*',
            'pg_attrdef',
            'information_schema.*',
            'table_schema.*',
            'telescope_*',
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
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case HttpLogDto::class:
                $config = ConfigEnum::HttpLog;
                /** @var HttpLogDto $dto */
                $type = $dto->type->value;
                $can = lhConfig($config, 'enabled')
                    && lhConfig($config, "{$type}.enabled")
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.{$type}.exclude", $dto)
                ;
                break;

            case ModelLogDto::class:
                $config = ConfigEnum::ModelLog;
                /** @var ModelLogDto $dto */
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case QueryLogDto::class:
                $config = ConfigEnum::QueryLog;
                /** @var QueryLogDto $dto */
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                    && $this->notFoundIgnoreTables($dto->info['tables'] ?? [])
                    && !Hlp::arraySearchValues($dto->info['tables'] ?? [], [lhConfig($config, 'table')])
                ;
                break;

            case QueueLogDto::class:
                $config = ConfigEnum::QueueLog;
                /** @var QueueLogDto $dto */
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                    && (($dto->info['class'] ?? null) !== QueueLogJob::class)
                ;
                break;

            case RouteLogDto::class:
                $config = ConfigEnum::RouteLog;
                /** @var RouteLogDto $dto */
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            case TelegramLogDto::class:
                $config = ConfigEnum::TelegramLog;
                /** @var TelegramLogDto $dto */
                $type = $dto->type;
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.{$type}.exclude", $dto)
                ;
                break;

            case ViewLogDto::class:
                $config = ConfigEnum::ViewLog;
                /** @var ViewLogDto $dto */
                $can = lhConfig($config, 'enabled')
                    && $this->notFoundConfigExclude("laravel-helper.{$config->value}.exclude", $dto)
                ;
                break;

            default:
                $can = true;
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
            $table === lhConfig(ConfigEnum::ConsoleLog, 'table') => ConsoleLog::class,
            $table === lhConfig(ConfigEnum::HttpLog, 'table') => HttpLog::class,
            $table === lhConfig(ConfigEnum::ModelLog, 'table') => ModelLog::class,
            $table === lhConfig(ConfigEnum::QueryLog, 'table') => QueryLog::class,
            $table === lhConfig(ConfigEnum::QueueLog, 'table') => QueueLog::class,
            $table === lhConfig(ConfigEnum::RouteLog, 'table') => RouteLog::class,
            $table === lhConfig(ConfigEnum::ViewLog, 'table') => ViewLog::class,

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
