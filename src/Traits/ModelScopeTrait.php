<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Dto\Scope\SortScopeDto;
use Atlcom\LaravelHelper\Enums\SortDirectionEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Трейт для подключения фильтров к модели
 * 
 * @method self|Builder|DefaultModel ofSort(array $sort)
 * @method self|Builder|DefaultModel ofPage(int $page, int $limit)
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
}
