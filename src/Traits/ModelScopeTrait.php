<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Dto\Scope\SortScopeDto;
use Atlcom\LaravelHelper\Dto\Table\TableFilterDto;
use Atlcom\LaravelHelper\Enums\FilterOperatorEnum;
use Atlcom\LaravelHelper\Enums\SortDirectionEnum;
use Atlcom\LaravelHelper\Services\ModelScopeService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Трейт для подключения фильтров к модели
 * 
 * @method self|Builder|DefaultModel ofSort(array $sort)
 * @method self|Builder|DefaultModel ofPage(int $page, int $limit)
 * @method self|Builder|DefaultModel ofFilters(array|null $requestFilters)
 * 
 * @mixin DefaultModel
 */
trait ModelScopeTrait
{
    /**
     * Фильтр: Сортировка
     *
     * @param Builder $query
     * @param array $sort
     * @return Builder
     */
    public function scopeOfSort(Builder $query, array $sort): Builder
    {
        return $query
            ->when($sort, static function ($query) use ($sort) {
                foreach ($sort as $sortDto) {
                    /** @var SortScopeDto $sortDto */
                    !$sortDto->field ?: $query->orderBy(
                        $sortDto->field,
                        (SortDirectionEnum::enumFrom($sortDto->direction) ?: SortDirectionEnum::enumDefault())->value,
                    );
                }
            });
    }


    /**
     * Фильтр: Пагинация
     *
     * @param Builder $query
     * @param int $page
     * @param int $limit
     * @return Builder
     */
    public function scopeOfPage(Builder $query, int $page, int $limit): Builder
    {
        return $query
            ->offset(($page - 1) * $limit)
            ->limit($limit);
    }


    /**
     * Применяет фильтры к запросу списка с пагинацией
     *
     * @param Builder $query
     * @param array|null $requestFilters
     * @return Builder
     */
    public function scopeOfFilters(Builder $query, ?array $requestFilters = null): Builder
    {
        $modelInstance     = $this;
        $modelScopeService = app(ModelScopeService::class);

        return method_exists($this, 'table')
            ? $query
                ->when(
                    ($modelTable = $this::table()) && $requestFilters,
                    static function ($query) use ($modelTable, $requestFilters, $modelInstance, $modelScopeService) {

                        // Применяем фильтры из модели
                        foreach ($modelTable->filters as $modelFilterName => $modelFilterData) {
                            $modelFilterDto = TableFilterDto::create($modelFilterData);

                            if ($requestFilterValue = $requestFilters[$modelFilterName] ?? null) {
                                $closure = $modelFilterDto->closure;

                                match ($modelFilterDto->operator) {
                                    FilterOperatorEnum::Equal
                                      => $query->where($modelFilterDto->column, '=', $requestFilterValue),
                                    FilterOperatorEnum::Like
                                       => $query->where($modelFilterDto->column, 'like', "%$requestFilterValue%"),
                                    FilterOperatorEnum::Ilike
                                      => $query->where($modelFilterDto->column, 'ilike', "%$requestFilterValue%"),
                                    FilterOperatorEnum::In
                                         => $query->whereIn($modelFilterDto->column, (array)$requestFilterValue),
                                    FilterOperatorEnum::Between
                                    => $query->when(
                                        is_array($requestFilterValue),
                                        static function ($query) use ($modelFilterDto, $requestFilterValue, $modelInstance, $modelScopeService) {
                                                $normalize = static function ($value) {
                                                    if ($value === null || $value === '') {
                                                        return null;
                                                    }

                                                    if ($value instanceof DateTimeInterface) {
                                                        return $value;
                                                    }

                                                    if (is_bool($value)) {
                                                        return (int)$value;
                                                    }

                                                    if (is_numeric($value)) {
                                                        $numericString = (string)$value;

                                                        return str_contains($numericString, '.')
                                                        ? (float)$numericString
                                                        : (int)$numericString;
                                                    }

                                                    return (string)$value;
                                                };

                                                $rawRange = array_values(
                                                array_pad(
                                                    array_slice((array)$requestFilterValue, 0, 2),
                                                    2,
                                                    null,
                                                ),
                                                );

                                                $range = array_map($normalize, $rawRange);

                                                [$columnExpression, $range] = $modelScopeService->prepareFilterRange(
                                                $modelInstance,
                                                $query,
                                                $modelFilterDto->column,
                                                $rawRange,
                                                $range,
                                                );

                                                [$from, $to] = $range;

                                                $shouldSwap = static function ($left, $right): bool {
                                                    if (
                                                    $left instanceof DateTimeInterface
                                                    && $right instanceof DateTimeInterface
                                                    ) {
                                                        return $left->getTimestamp() > $right->getTimestamp();
                                                    }

                                                    if (is_numeric($left) && is_numeric($right)) {
                                                        return (float)$left > (float)$right;
                                                    }

                                                    if (is_string($left) && is_string($right)) {
                                                        return strcmp($left, $right) > 0;
                                                    }

                                                    return false;
                                                };

                                                // Переставляем границы, если они переданы в обратном порядке
                                                if ($from !== null && $to !== null && $shouldSwap($from, $to)) {
                                                    [$from, $to] = [$to, $from];
                                                }

                                                // Поддерживаем открытые границы интервала: >= или <= при null
                                                match (true) {
                                                    $from !== null && $to !== null
                                                    => $query->whereBetween($columnExpression, [$from, $to]),
                                                    $from !== null
                                                                    => $query->where($columnExpression, '>=', $from),
                                                    $to !== null
                                                                      => $query->where($columnExpression, '<=', $to),

                                                    default                        => null,
                                                };
                                            },
                                    ),
                                    FilterOperatorEnum::Closure
                                    => !is_callable($closure) ?: $closure($query, $requestFilterValue),

                                    default                     => null,
                                };
                            }
                        }

                    }
                )
            : $query;
    }
}
