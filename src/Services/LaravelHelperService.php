<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Dto;
use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Jobs\ConsoleLogJob;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Jobs\ModelLogJob;
use Atlcom\LaravelHelper\Jobs\QueryLogJob;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Jobs\RouteLogJob;
use Atlcom\LaravelHelper\Jobs\TelegramLogJob;
use Atlcom\LaravelHelper\Jobs\ViewLogJob;
use Atlcom\LaravelHelper\Models\QueueLog;

/**
 * Сервис пакета laravel-helper
 */
class LaravelHelperService
{
    /**
     * Проверяет параметры конфига laravel-helper
     *
     * @return void
     */
    public function checkConfig(): void
    {
        $config = Helper::arrayDot((array)config('laravel-helper') ?? []);

        !(
            ($config[$param = 'console_log.queue'] ?? null)
            && ($config[$param = 'console_log.connection'] ?? null)
            && ($config[$param = 'console_log.table'] ?? null)
            && ($config[$param = 'console_log.model'] ?? null)
            && ($config[$param = 'console_log.cleanup_days'] ?? null)

            && ($config[$param = 'http_log.queue'] ?? null)
            && ($config[$param = 'http_log.connection'] ?? null)
            && ($config[$param = 'http_log.table'] ?? null)
            && ($config[$param = 'http_log.model'] ?? null)
            && ($config[$param = 'http_log.cleanup_days'] ?? null)

            && ($config[$param = 'model_log.queue'] ?? null)
            && ($config[$param = 'model_log.connection'] ?? null)
            && ($config[$param = 'model_log.table'] ?? null)
            && ($config[$param = 'model_log.model'] ?? null)
            && ($config[$param = 'model_log.cleanup_days'] ?? null)
            && ($config[$param = 'model_log.drivers'] ?? null)

            && ($config[$param = 'route_log.queue'] ?? null)
            && ($config[$param = 'route_log.connection'] ?? null)
            && ($config[$param = 'route_log.table'] ?? null)
            && ($config[$param = 'route_log.model'] ?? null)

            && ($config[$param = 'queue_log.queue'] ?? null)
            && ($config[$param = 'queue_log.connection'] ?? null)
            && ($config[$param = 'queue_log.table'] ?? null)
            && ($config[$param = 'queue_log.model'] ?? null)
            && ($config[$param = 'queue_log.cleanup_days'] ?? null)

            && ($config[$param = 'telegram_log.queue'] ?? null)

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
            $data = Helper::arrayDot($data);
            $dataCheck = [];

            foreach ($data as $key => $val) {
                $dataCheck[] = "{$key}={$val}";
            }

            return empty(Helper::arraySearchValues($dataCheck, $exclude));
        }

        return true;
    }


    /**
     * Проверяет массив $tables на совпадение с массивом игнорируемых таблиц
     *
     * @param array $tables
     * @return bool
     */
    public function notFoundIgnoreTables(array $tables = []): bool
    {
        static $ignoreTables = null;

        $ignoreTables ??= [
            config('laravel-helper.console_log.table'),
            config('laravel-helper.http_log.table'),
            config('laravel-helper.model_log.table'),
            config('laravel-helper.queue_log.table'),
            config('laravel-helper.query_log.table'),
            config('laravel-helper.view_log.table'),
            config('cache.stores.database.table', 'cache'),
            'pg_catalog.pg_collation',
            'pg_attrdef',
        ];

        return empty(Helper::arraySearchValues($tables, $ignoreTables));
    }


    /**
     * Проверяет на возможность отправки Dto в очередь для обработки в job
     *
     * @return bool
     */
    public function canDispatch(Dto $dto): bool
    {
        switch ($dto::class) {

            case ConsoleLogDto::class:
                /** @var ConsoleLogDto $dto */
                $can = config('laravel-helper.console_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.console_log.exclude', $dto)
                ;
                break;

            case HttpLogDto::class:
                /** @var HttpLogDto $dto */
                $type = $dto->type->value;
                $can = config("laravel-helper.http_log.{$type}.enabled")
                    && $this->notFoundConfigExclude("laravel-helper.http_log.{$type}.exclude", $dto)
                ;
                break;

            case ModelLogDto::class:
                /** @var ModelLogDto $dto */
                $can = config('laravel-helper.model_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.model_log.exclude', $dto)
                ;
                break;

            case QueryLogDto::class:
                /** @var QueryLogDto $dto */
                $can = config('laravel-helper.query_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.query_log.exclude', $dto)
                    && !Helper::arraySearchValues($dto->info['tables'], [config('laravel-helper.query_log.table')])
                    // && $this->notFoundIgnoreTables($dto->info['tables'])
                ;
                break;

            case QueueLogDto::class:
                /** @var QueueLogDto $dto */
                $can = config('laravel-helper.queue_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.queue_log.exclude', $dto)
                    // && !in_array($dto->info['class'] ?? null, [
                    //     ConsoleLogJob::class,
                    //     HttpLogJob::class,
                    //     ModelLogJob::class,
                    //     QueryLogJob::class,
                    //     QueueLogJob::class,
                    //     RouteLogJob::class,
                    //     TelegramLogJob::class,
                    //     ViewLogJob::class,
                    // ])
                ;
                break;

            case RouteLogDto::class:
                /** @var RouteLogDto $dto */
                $can = config('laravel-helper.route_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.route_log.exclude', $dto)
                ;
                break;

            case TelegramLogDto::class:
                /** @var TelegramLogDto $dto */
                $type = $dto->type;
                $can = config('laravel-helper.telegram_log.enabled')
                    && $this->notFoundConfigExclude("laravel-helper.telegram_log.{$type}.exclude", $dto)
                ;
                break;

            case ViewLogDto::class:
                /** @var ViewLogDto $dto */
                $can = config('laravel-helper.view_log.enabled')
                    && $this->notFoundConfigExclude('laravel-helper.view_log.exclude', $dto)
                ;
                break;

            default:
                $can = true;
        }

        return $can;
    }
}
