<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnitEnum;

/**
 * @internal
 * Сервис преобразования входных фильтров в корректные типы и выражения
 */
class ModelScopeService extends DefaultService
{
    /**
     * Приводит массив значений фильтра к типам столбца модели
     *
     * @param DefaultModel $model
     * @param string|null $column
     * @param array $values
     * @return array
     */
    public function castFilterArrayValues(DefaultModel $model, ?string $column, array $values): array
    {
        return array_map(fn ($value) => $this->castFilterValue($model, $column, $value), $values);
    }


    /**
     * Приводит значение фильтра к типу столбца модели
     *
     * @param DefaultModel $model
     * @param string|null $column
     * @param mixed $value
     * @return mixed
     */
    public function castFilterValue(DefaultModel $model, ?string $column, mixed $value): mixed
    {
        if ($value === null || !$column) {
            return $value;
        }

        $castType = $this->getFilterColumnCastType($model, $column);

        if (!$castType) {
            return $value;
        }

        if (class_exists($castType) && is_subclass_of($castType, UnitEnum::class)) {
            if ($value instanceof UnitEnum && $value instanceof $castType) {
                return $value;
            }

            if (is_subclass_of($castType, BackedEnum::class) && method_exists($castType, 'tryFrom')) {
                return $castType::tryFrom($value);
            }

            return $value;
        }

        return match ($castType) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint'                       => (int)$value,
            'real', 'float', 'double', 'double precision', 'decimal', 'numeric'     => (float)$value,
            'bool', 'boolean'                                                       => filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ) ?? (bool)$value,
            'string', 'text', 'uuid'                                                => (string)$value,
            'date', 'datetime', 'immutable_date', 'immutable_datetime', 'timestamp' => $this->castFilterDateTime(
                $value,
            ),

            default                                                                 => $value,
        };
    }


    /**
     * Возвращает тип кастинга для столбца модели
     *
     * @param DefaultModel $model
     * @param string|null $column
     * @return string|null
     */
    public function getFilterColumnCastType(DefaultModel $model, ?string $column): ?string
    {
        if (!$column || !method_exists($model, 'getCasts')) {
            return null;
        }

        $casts = $model->getCasts();
        $cast  = $casts[$column] ?? null;

        if (!$cast) {
            return null;
        }

        if (class_exists($cast)) {
            return $cast;
        }

        if (is_string($cast) && str_contains($cast, ':')) {
            $cast = strstr($cast, ':', true) ?: $cast;
        }

        return is_string($cast) ? strtolower($cast) : null;
    }


    /**
     * Преобразует значение фильтра к Carbon-инстансу
     *
     * @param mixed $value
     * @return CarbonInterface|null
     */
    public function castFilterDateTime(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string)$value);
        } catch (Throwable) {
            return null;
        }
    }


    /**
     * Подготавливает диапазон фильтра и выражение колонки для whereBetween
     *
     * @param DefaultModel $model
     * @param Builder $query
     * @param string|null $column
     * @param array $rawRange
     * @param array $normalizedRange
     * @return array{0:string|Expression|null,1:array}
     */
    public function prepareFilterRange(
        DefaultModel $model,
        Builder $query,
        ?string $column,
        array $rawRange,
        array $normalizedRange,
    ): array {
        $castType = $this->getFilterColumnCastType($model, $column);

        if ($this->shouldCastColumnRangeToNumeric($castType, $rawRange)) {
            return [
                $this->castFilterColumnToNumericExpression($query, $column),
                $this->castFilterArrayToNumeric($rawRange),
            ];
        }

        return [$column, $this->castFilterArrayValues($model, $column, $normalizedRange)];
    }


    /**
     * Определяет необходимость перевода текстовой колонки в numeric
     *
     * @param string|null $castType
     * @param array $rawRange
     * @return bool
     */
    public function shouldCastColumnRangeToNumeric(?string $castType, array $rawRange): bool
    {
        if (!$castType) {
            return false;
        }

        $stringCasts = ['string', 'text'];

        if (!in_array($castType, $stringCasts, true)) {
            return false;
        }

        foreach ($rawRange as $value) {
            if (is_numeric($value)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Возвращает выражение с приведением текстовой колонки к numeric
     *
     * @param Builder $query
     * @param string|null $column
     * @return Expression|string|null
     */
    public function castFilterColumnToNumericExpression(Builder $query, ?string $column): Expression|string|null
    {
        if (!$column) {
            return $column;
        }

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $query->getConnection();
        $grammar    = $connection->getQueryGrammar();
        $wrapped    = $grammar->wrap($column);

        return DB::raw("({$wrapped})::numeric");
    }


    /**
     * Приводит значения диапазона к numeric для запросов
     *
     * @param array $values
     * @return array
     */
    public function castFilterArrayToNumeric(array $values): array
    {
        return array_map(static function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            return is_numeric($value) ? $value + 0 : null;
        }, $values);
    }
}
