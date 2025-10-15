<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Dto\Scope\SortScopeDto;
use Atlcom\LaravelHelper\Dto\Table\TableFilterDto;
use Atlcom\LaravelHelper\Enums\FilterOperatorEnum;
use Atlcom\LaravelHelper\Enums\SortDirectionEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Трейт для подключения фильтров к модели
 * 
 * @method self|Builder|DefaultModel ofSort(array $sort)
 * @method self|Builder|DefaultModel ofPage(int $page, int $limit)
 * @method self|Builder|DefaultModel ofFilters(int $page, int $limit)
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
        return method_exists($this, 'table')
            ? $query
                ->when(
                    ($modelTable = $this::table()) && $requestFilters,
                    static function ($query) use ($modelTable, $requestFilters) {

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
                                    => $query->whereBetween($modelFilterDto->column, (array)$requestFilterValue),
                                    FilterOperatorEnum::Closure
                                    => !is_callable($closure) ?: $closure($query, $requestFilterValue),

                                    default => null,
                                };
                            }
                        }

                    }
                )
            : $query;
    }
}
