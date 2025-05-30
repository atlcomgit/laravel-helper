<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;

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
}
