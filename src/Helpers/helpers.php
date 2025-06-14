<?php

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ExceptionDto;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Listeners\TelegramLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;


if (!function_exists('isDebug')) {
    /**
     * Возвращает флаг окружения APP_DEBUG
     *
     * @return bool
     */
    function isDebug(): bool
    {
        return (bool)config('laravel-helper.app.debug');
    }
}


if (!function_exists('isDebugData')) {
    /**
     * Возвращает флаг окружения APP_DEBUG_DATA
     *
     * @return bool
     */
    function isDebugData(): bool
    {
        return (bool)config('laravel-helper.app.debug_data');
    }
}


if (!function_exists('isDebugTrace')) {
    /**
     * Возвращает флаг окружения APP_DEBUG_TRACE
     *
     * @return bool|null
     */
    function isDebugTrace(): bool
    {
        return (bool)config('laravel-helper.app.debug_trace');
    }
}


if (!function_exists('isLocal')) {
    /**
     * Проверяет на локальное окружение
     *
     * @return bool
     */
    function isLocal(): bool
    {
        return in_array(config('app.env', null), ['local']);
    }
}


if (!function_exists('isTesting')) {
    /**
     * Проверяет на тестовое окружение
     *
     * @return bool
     */
    function isTesting(): bool
    {
        return in_array(env('APP_ENV', null), ['test', 'testing']);
    }
}


if (!function_exists('isDev')) {
    /**
     * Проверяет на dev окружение
     *
     * @return bool
     */
    function isDev(): bool
    {
        return in_array(config('app.env', null), ['develop', 'dev']);
    }
}


if (!function_exists('isProd')) {
    /**
     * Проверяет на боевое окружение
     *
     * @return bool
     */
    function isProd(): bool
    {
        return in_array(config('app.env', null), ['production', 'prod', 'master']);
    }
}


if (!function_exists('queue')) {
    /**
     * Ставит job в очередь и запускает её
     *
     * @param string $classJob
     * @param Dto $dto
     * @return void
     */
    function queue(string $classJob, Dto $dto, ?string $queueName = null): void
    {
        static $queued = [];
        $queueHash = $dto->getHash($queueName ?? '');

        // Если постановка в очередь не из самой очереди и не повторная
        if (!($queued[$queueHash] ?? null)) { // ограничение повторной отправки
            $queued[$queueHash] = true;

            isTesting()
                ? $classJob::dispatchSync($dto)
                : (!class_exists($classJob) ?: $classJob::dispatch($dto)->onQueue($queueName));
        }
    }
}


if (!function_exists('sql')) {
    /**
     * Возвращает сырой sql запрос c заменой плейсхолдеров
     *
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return string
     */
    function sql(EloquentBuilder|QueryBuilder|string $builder, array $bindings = []): string
    {
        return match (true) {
            $builder instanceof EloquentBuilder => $builder->toRawSql(),
            $builder instanceof QueryBuilder => $builder->toRawSql(),

            default => Hlp::sqlBindings(
                is_string($builder) ? $builder : $builder->toSql(),
                is_string($builder) ? $bindings : $builder->getBindings()
            ),
        };
    }
}


if (!function_exists('json')) {
    /**
     * Возвращает json строку
     *
     * @param mixed $data
     * @return string
     */
    function json(mixed $data): string
    {
        return json_encode($data, Hlp::jsonFlags()) ?? '{}';
    }
}


if (!function_exists('telegram')) {
    /**
     * Отправляет сообщение в телеграм
     *
     * @param mixed $data
     * @param string|TelegramTypeEnum $type
     * @param array $context
     * @return void
     */
    function telegram(mixed $data, string|TelegramTypeEnum $type = TelegramTypeEnum::Debug, array $context = []): void
    {
        try {
            $log = Log::build(['driver' => 'custom', 'via' => TelegramLogger::class]);
            $data instanceof Throwable
                ? ExceptionDto::createFromException(exception: $data)
                : match (TelegramTypeEnum::enumFrom($type)) {
                    TelegramTypeEnum::Info => $log->info(json($data), $context),
                    TelegramTypeEnum::Error => $log->error(json($data), $context),
                    TelegramTypeEnum::Warning => $log->warning(json($data), $context),
                    TelegramTypeEnum::Debug => $log->debug(json($data), $context),

                    default => $log->notice(json($data), [...$context, 'level' => $type]),
                };
        } catch (Throwable $e) {
            // !isTesting() ?: throw $e;
        }
    }
}

if (!function_exists('user')) {
    /**
     * Возвращает модель авторизованного пользователя или null
     * 
     * @param bool $returnOnlyId
     * @return Authenticatable|int|string|null
     */
    function user(bool $returnOnlyId = false): ?Authenticatable
    {
        try {
            $user = isTesting()
                ? ($returnOnlyId ? auth()->id() : auth()->user())
                : (request()->bearerToken() ? ($returnOnlyId ? auth()->id() : auth()->user()) : null);
        } catch (Throwable $e) {
            $user = null;
        }

        return $user;
    }
}


if (!function_exists('ip')) {
    /**
     * Возвращает ip адрес из запроса
     * 
     * @return string|null
     */
    function ip(): ?string
    {
        return (request()->headers->all()['x-forwarded-for'][0] ?? null)
            ?: (request()->headers->all()['x-real-ip'][0] ?? null)
            ?: request()->getClientIp();
    }
}


if (!function_exists('uuid')) {
    /**
     * Возвращает uuid
     * 
     * @return string
     */
    function uuid(): string
    {
        return (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid())->toString();
    }
}
