<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Exceptions\HelperException;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Throwable;

/**
 * Трейт для работы с профилированием методов класса
 */
trait ProfilerLogTrait
{
    public function __call($method, $args)
    {
        $dto = static::start($method, $args, false);

        try {
            // $dto->result = call_user_func_array([static::class, $method], $args);
            method_exists(static::class, $method)
                ?: throw new HelperException('Метод не найден: ' . $dto->info['function']);

            $dto->result = $this->$method(...$args);
            $dto->status = ProfilerLogStatusEnum::Success;

        } catch (Throwable $exception) {
            $dto->exception = $exception;
            $dto->status = ProfilerLogStatusEnum::Exception;

            throw $exception;

        } finally {
            return static::finish($dto)->result;
        }
    }


    public static function __callStatic($method, $args)
    {
        $dto = static::start($method, $args, false);

        try {
            // $dto->result = call_user_func_array([static::class, $method], $args);
            method_exists(static::class, $method)
                ?: throw new HelperException('Метод не найден: ' . $dto->info['function']);

            $dto->result = static::$method(...$args);
            $dto->status = ProfilerLogStatusEnum::Success;

        } catch (Throwable $exception) {
            $dto->exception = $exception;
            $dto->status = ProfilerLogStatusEnum::Exception;

            throw $exception;

        } finally {
            return static::finish($dto)->result;
        }
    }


    /**
     * Запуск профилирования метода класса
     *
     * @param mixed $method
     * @param mixed $arguments
     * @param mixed $isStatic
     * @return ProfilerLogDto
     */
    private static function start(&$method, &$arguments, $isStatic): ProfilerLogDto
    {
        $method = ltrim($method, '_');
        $function = static::class . ($isStatic ? '::' : '->') . $method . '()';

        static $profilerLogs = [];

        $dto = $profilerLogs[$function] ?? ProfilerLogDto::create(
            class: static::class,
            method: $method,
            isStatic: $isStatic,
            arguments: $arguments,
            info: [
                'function' => $function,
            ],
        );
        $dto->startTime = (string)now()->getTimestampMs();
        $dto->startMemory = memory_get_usage();
        $dto->count++;

        // !lhConfig(ConfigEnum::ProfilerLog, 'store_on_start') ?: 
        $dto->dispatch();

        return $profilerLogs[$function] = $dto;
    }


    /**
     * Завершение профилирования метода класса
     *
     * @param ProfilerLogDto $dto
     * @return ProfilerLogDto
     */
    private static function finish(ProfilerLogDto $dto): ProfilerLogDto
    {
        $duration = $dto->getDuration();
        $memory = $dto->getMemory();

        $dto->duration = ($dto->duration ?? 0) + $duration;
        $dto->durationMin = is_null($dto->durationMin) ? $duration : min($dto->durationMin, $duration);
        $dto->durationAvg = $dto->duration / $dto->count;
        $dto->durationMax = is_null($dto->durationMax) ? $duration : max($dto->durationMax, $duration);

        $dto->memory = ($dto->memory ?? 0) + $memory;
        $dto->memoryMin = is_null($dto->memoryMin) ? $memory : min($dto->memoryMin, $memory);
        $dto->memoryAvg = (int)($dto->memory / $dto->count);
        $dto->memoryMax = is_null($dto->memoryMax) ? $memory : max($dto->memoryMax, $memory);

        $dto->info = [
            ...$dto->info,
            'duration' => [
                'min' => Hlp::timeSecondsToString(value: $dto->durationMin, withMilliseconds: true),
                'avg' => Hlp::timeSecondsToString(value: $dto->durationAvg, withMilliseconds: true),
                'max' => Hlp::timeSecondsToString(value: $dto->durationMax, withMilliseconds: true),
                'sum' => Hlp::timeSecondsToString(value: $dto->duration, withMilliseconds: true),
            ],
            'memory' => [
                'min' => Hlp::sizeBytesToString($dto->memoryMin),
                'avg' => Hlp::sizeBytesToString($dto->memoryAvg),
                'max' => Hlp::sizeBytesToString($dto->memoryMax),
                'sum' => Hlp::sizeBytesToString($dto->memory),
            ],
            'count' => Hlp::stringPlural($dto->count, ['запусков', 'запуск', 'запуска']),
            'trace' => Hlp::arrayExcludeTraceVendor(debug_backtrace()),
        ];

        $dto->info['results'] ??= [];
        $dto->info['results'][$dto->count] = ($dto->status === ProfilerLogStatusEnum::Exception)
            ? [
                'type' => $dto->exception::class,
                'message' => $dto->exception->getMessage(),
            ]
            : [
                'type' => is_object($dto->result) ? $dto->result::class : gettype($dto->result),
                'value' => $dto->result,
            ];
        $dto->exception = Hlp::exceptionToString($dto->exception);

        $dto->dispatch();

        return $dto;

    }
}
