<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Builders;

use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Traits\BuilderCacheTrait;
use Illuminate\Database\Eloquent\Builder;

/**
 * Переопределенный конструктор запросов Query Builder
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class EloquentBuilder extends Builder
{
    use BuilderCacheTrait;

    
    /**
     * @override
     * Выполняет запрос как оператор «select»
     * @see parent::get()
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    // #[Override()]
    public function get($columns = ['*'])
    {
        //?!? проверить кеш

        $result = parent::get($columns);

        //?!? сохранить кеш

        return $result;
    }


    /**
     * @override
     * Выполняет запрос и возвращает первую запись
     * @see parent::first()
     *
     * @param  array|string  $columns
     * @return TValue|null
     */
    // #[Override()]
    public function first($columns = ['*'])
    {
        //?!? проверить кеш
        // dd($this->getModels());
        $result = parent::first($columns);

        //?!? сохранить кеш

        return $result;
    }
}
