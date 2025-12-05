<?php

declare(strict_types=1);

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Dto\ExceptionDto;
use Atlcom\LaravelHelper\Dto\HttpCacheConfigDto;
use Atlcom\LaravelHelper\Dto\HttpLogConfigDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Loggers\TelegramLogLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;


if (!function_exists('lhConfig')) {
    /**
     * Возвращает конфиг по типу лога
     * 
     * @param ConfigEnum $configType
     * @param string $configName
     * @param mixed $default
     * @return mixed
     */
    function lhConfig(ConfigEnum $configType, string $configName, mixed $default = null): mixed
    {
        return Lh::config($configType, $configName, $default);
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция lhConfig() уже определена в приложении');
}


if (!function_exists('isDebug')) {
    /**
     * Возвращает флаг окружения APP_DEBUG
     *
     * @return bool
     */
    function isDebug(): bool
    {
        return (bool)Lh::config(ConfigEnum::App, 'debug');
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isDebug() уже определена в приложении');
}


if (!function_exists('isDebugData')) {
    /**
     * Возвращает флаг окружения APP_DEBUG_DATA
     *
     * @return bool
     */
    function isDebugData(): bool
    {
        return (bool)Lh::config(ConfigEnum::App, 'debug_data');
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isDebugData() уже определена в приложении');
}


if (!function_exists('isDebugTrace')) {
    /**
     * Возвращает флаг окружения APP_DEBUG_TRACE
     *
     * @return bool|null
     */
    function isDebugTrace(): bool
    {
        return (bool)Lh::config(ConfigEnum::App, 'debug_trace');
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isDebugTrace() уже определена в приложении');
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
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isLocal() уже определена в приложении');
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
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isDev() уже определена в приложении');
}


if (!function_exists('isTesting')) {
    /**
     * Проверяет на тестовое окружение
     *
     * @return bool
     */
    function isTesting(): bool
    {
        return in_array(env('APP_ENV', null), ['testing'])
            || ApplicationDto::restore()?->type === ApplicationTypeEnum::Testing;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isTesting() уже определена в приложении');
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
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isProd() уже определена в приложении');
}


if (!function_exists('isCommand')) {
    /**
     * Проверяет на запуск приложения из консольной команды
     *
     * @return bool
     */
    function isCommand(): bool
    {
        return ApplicationDto::restore()?->type === ApplicationTypeEnum::Command;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isCommand() уже определена в приложении');
}


if (!function_exists('isHttp')) {
    /**
     * Проверяет на запуск приложения из http запроса
     *
     * @return bool
     */
    function isHttp(): bool
    {
        return ApplicationDto::restore()?->type === ApplicationTypeEnum::Http;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isHttp() уже определена в приложении');
}


if (!function_exists('isQueue')) {
    /**
     * Проверяет на запуск приложения из очереди
     *
     * @return bool
     */
    function isQueue(): bool
    {
        return ApplicationDto::restore()?->type === ApplicationTypeEnum::Queue;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция isQueue() уже определена в приложении');
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
} else {
    throw new LaravelHelperException('Laravel_helper: Функция queue() уже определена в приложении');
}


if (!function_exists('sql')) {
    /**
     * Возвращает сырой sql запрос c заменой плейсхолдеров и усечением длинных значений до 1000 символов
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
                is_string($builder) ? Hlp::arrayTruncateStringValues($bindings, 1000) : $builder->getBindings()
            ),
        };
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция sql() уже определена в приложении');
}


if (!function_exists('json')) {
    /**
     * Возвращает json строку
     *
     * @param mixed $data
     * @param int $flags
     * @return string
     */
    function json(mixed $data, int $flags = 0): string
    {
        return json_encode($data, Hlp::jsonFlags() | $flags) ?? '{}';
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция json() уже определена в приложении');
}


if (!function_exists('telescope')) {
    /**
     * Включает/Отключает логи telescope
     *
     * @param bool|null $enabled
     * @return bool
     */
    function telescope(?bool $enabled = null): bool
    {
        $telescopeClass = '\Laravel\Telescope\Telescope';

        if (class_exists($telescopeClass)) {
            if (is_null($enabled)) {
                return $telescopeClass::isRecording();
            }

            $enabled
                ? $telescopeClass::startRecording()
                : $telescopeClass::stopRecording();

            return $enabled;
        }

        return false;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция telescope() уже определена в приложении');
}


if (!function_exists('telegram')) {
    /**
     * Отправляет сообщение в телеграм
     *
     * @param mixed $data
     * @param string|TelegramTypeEnum|null $type
     * @param array $context
     * @return void
     */
    function telegram(mixed $data, string|TelegramTypeEnum|null $type = TelegramTypeEnum::Debug, array $context = []): void
    {
        if ($type === null) {
            return;
        }

        try {
            $log = Log::build(['driver' => 'custom', 'via' => TelegramLogLogger::class]);
            $data instanceof Throwable
                ? ExceptionDto::createFromException(exception: $data)
                : match (TelegramTypeEnum::enumFrom($type)) {
                    TelegramTypeEnum::Info => $log->info(json($data), $context),
                    TelegramTypeEnum::Error => $log->error(json($data), $context),
                    TelegramTypeEnum::Warning => $log->warning(json($data), $context),
                    TelegramTypeEnum::Notice => $log->notice(json($data), $context),
                    TelegramTypeEnum::Alert => $log->alert(json($data), $context),
                    TelegramTypeEnum::Debug => $log->debug(json($data), $context),

                    default => $log->debug(json($data), [...$context, 'level' => $type]),
                };
        } catch (Throwable $e) {
            // !isTesting() ?: throw $e;
        }
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция telegram() уже определена в приложении');
}

if (!function_exists('user')) {
    /**
     * Возвращает модель авторизованного пользователя или null
     * 
     * @param bool $returnOnlyId
     * @return Authenticatable|App\Models\User|App\Models\User\User|App\Domains\Crm\Models\User|int|string|null
     */
    function user(bool $returnOnlyId = false): Authenticatable|int|string|null
    {
        // Флаг от зацикливания
        static $functionRunning = false;

        if ($functionRunning) {
            return null;
        }

        $functionRunning = true;
        $cacheKey = 'helpers:' . __FUNCTION__ . $returnOnlyId;
        $user = Hlp::cacheRuntimeGet($cacheKey);

        if (!$user || isTesting()) {
            try {
                $user = isTesting()
                    ? ($returnOnlyId ? auth()->id() : auth()->user())
                    : (request()->bearerToken() ? ($returnOnlyId ? auth()->id() : auth()->user()) : null);
                !$user ?: Hlp::cacheRuntimeSet($cacheKey, $user);
            } catch (Throwable $e) {
                $user = null;
            }
        }
        $functionRunning = false;

        return $user;
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция user() уже определена в приложении');
}


if (!function_exists('ip')) {
    /**
     * Возвращает ip адрес из запроса
     * 
     * @return string|null
     */
    function ip(): ?string
    {
        return Hlp::cacheRuntime(
            'helpers' . __FUNCTION__,
            static fn () => (request()->headers->all()['x-forwarded-for'][0] ?? null)
            ?: (request()->headers->all()['x-real-ip'][0] ?? null)
            ?: request()->getClientIp()
        );
    }
} else {
    throw new LaravelHelperException('Laravel_helper: Функция ip() уже определена в приложении');
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
} else {
    throw new LaravelHelperException('Laravel_helper: Функция uuid() уже определена в приложении');
}


if (!function_exists('withHttpCache')) {
    /**
     * Возвращает кабинет запроса
     *
     * @return string
     */
    function withHttpCache(?HttpCacheConfigDto $dto = null): string
    {
        return Hlp::stringConcat(':', 'withHttpCache', Hlp::cryptEncode($dto, 'cache'));
    }
}


if (!function_exists('withHttpLog')) {
    /**
     * Возвращает кабинет запроса
     *
     * @return string
     */
    function withHttpLog(?HttpLogConfigDto $dto = null): string
    {
        return Hlp::stringConcat(':', 'withHttpLog', Hlp::cryptEncode($dto, 'log'));
    }
}
