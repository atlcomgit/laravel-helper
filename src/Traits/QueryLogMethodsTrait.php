<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Трейт логирования query запросов
 *
 * Отвечает за создание, обновление и обработку ошибок логов запросов.
 */
trait QueryLogMethodsTrait
{
    /**
     * Сохраняет лог перед query запросом
     *
     * @param EloquentBuilder|QueryBuilder|string $builder
     * @return array<QueryLogDto>
     */
    protected function createQueryLog(EloquentBuilder|QueryBuilder|string $builder): array
    {
        $result = [];
        $exceedDurationMs = false;

        if (
            !($enabled = Lh::config(ConfigEnum::QueryLog, 'enabled'))
            || !(
                $this->withQueryLog === true
                || ($this->withQueryLog !== false && Lh::config(ConfigEnum::QueryLog, 'global'))
            )
        ) {
            // Лог запроса выключен, проверяем время превышения
            $exceedDurationMs = Lh::config(ConfigEnum::QueryLog, 'exceed_duration_ms') ?? false;

            // Если сервис лога запросов выключен или время превышения не задано, то выходим
            if (!$enabled || !is_numeric($exceedDurationMs)) {
                return $result;
            }
        }

        $this->setQueryLogClass($this::class);

        if ($this->getQueryLogClass() !== $this::class) {
            return $result;
        }

        $sql = app(QueryCacheService::class)->getSqlFromBuilder($builder);
        $models = [$this];
        $classes = [];
        $ids = [];

        foreach ($models as $model) {
            $classes[$model::class] = true;
            !($model instanceof Model) ?: $ids[$model::class][] = $model->{$model->getKeyName()};
        }

        foreach (array_keys($classes) ?: [$this::class] as $class) {
            /** @var Model|QueryBuilder $model */
            $dto = QueryLogDto::create(
                name: Hlp::pathClassName($class),
                query: $sql,
                info: [
                    'class'              => $class,
                    'tables'             => Hlp::sqlTables($sql),
                    'fields'             => Hlp::sqlFields($sql),
                    'ids'                => $ids[$class] ?? null,
                    'query_length'       => Hlp::stringLength($sql),
                    ...(Lh::config(ConfigEnum::App, 'debug_trace')
                        ? [
                            'trace' => Lh::config(ConfigEnum::App, 'debug_trace_vendor')
                                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                                : Hlp::arrayExcludeTraceVendor(
                                    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                                )
                        ]
                        : []
                    ),
                    'exceed_duration_ms' => is_numeric($exceedDurationMs)
                        ? (int)$exceedDurationMs
                        : false,
                ],
            );

            if (Lh::canDispatch($dto)) {
                !(Lh::config(ConfigEnum::QueryLog, 'store_on_start') && !is_numeric($exceedDurationMs))
                    ?: $dto->dispatch();
                $result[] = $dto;
            }
        }

        return $result;
    }


    /**
     * Сохраняет лог после query запроса
     *
     * @param array<QueryLogDto> $arrayQueryLogDto
     * @param mixed $result
     * @param string|null $cacheKey
     * @param bool|null $isCached
     * @param bool|null $isFromCache
     * @param bool $status
     * @return void
     */
    protected function updateQueryLog(
        array $arrayQueryLogDto,
        mixed &$result,
        ?string $cacheKey = null,
        ?bool $isCached = null,
        ?bool $isFromCache = null,
        bool $status = false,
    ): void {
        foreach ($arrayQueryLogDto as $dto) {
            /** @var QueryLogDto $dto */
            $dto->cacheKey = $cacheKey;
            $dto->isCached = is_null($isCached) ? false : $isCached;
            $dto->isFromCache = is_null($isFromCache) ? false : $isFromCache;
            $dto->status = $status
                ? QueryLogStatusEnum::Success
                : QueryLogStatusEnum::Failed;
            $dto->isUpdated = Lh::config(ConfigEnum::QueryLog, 'store_on_start');
            $dto->duration = $dto->getDuration();
            $dto->memory = $dto->getMemory();
            $dto->count = match (true) {
                $result instanceof Collection => $result->count(),
                $result instanceof Model => 1,
                is_array($result) => count($result),

                default => Hlp::castToInt((bool)$result),
            };
            $dto->info = [
                ...($dto->info ?? []),
                'duration'    => Hlp::timeSecondsToString(
                    value: $dto->duration,
                    withMilliseconds: true,
                ),
                'memory'      => Hlp::sizeBytesToString($dto->memory),
                'count'       => Hlp::stringPlural(
                    $dto->count,
                    ['записей', 'запись', 'записи'],
                ),
                'result_type' => match (true) {
                    is_object($result) => $result::class,

                    default => gettype($result)
                },
            ];

            // Установленное время превышения запроса
            $exceedDurationMs = $dto->info['exceed_duration_ms'] ?? false;

            // Если включен лог запроса или запрос превысил установленное время
            if (
                !is_numeric($exceedDurationMs)
                || (int)($dto->duration * 1000) >= (int)$exceedDurationMs
            ) {
                $dto->dispatch();
            }
        }
    }


    /**
     * Сохраняет лог при ошибке query запроса
     *
     * @param array<QueryLogDto> $arrayQueryLogDto
     * @param Throwable $exception
     * @return void
     */
    protected function failQueryLog(
        array $arrayQueryLogDto,
        Throwable $exception,
    ): void {
        foreach ($arrayQueryLogDto as $dto) {
            /** @var QueryLogDto $dto */
            $dto->status = QueryLogStatusEnum::Failed;
            $dto->isUpdated = Lh::config(ConfigEnum::QueryLog, 'store_on_start');
            $dto->duration = $dto->getDuration();
            $dto->memory = $dto->getMemory();
            $dto->info = [
                ...($dto->info ?? []),
                'duration'  => Hlp::timeSecondsToString(
                    value: $dto->duration,
                    withMilliseconds: true,
                ),
                'memory'    => Hlp::sizeBytesToString($dto->memory),
                'exception' => Hlp::exceptionToArray($exception),
            ];

            $dto->dispatch();
        }
    }
}
