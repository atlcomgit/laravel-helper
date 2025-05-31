<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Builders;

use Atlcom\LaravelHelper\Traits\BuilderCacheTrait;
use Illuminate\Database\Query\Builder;

/**
 * Переопределенный конструктор запросов Eloquent Builder
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 * @mixin \Illuminate\Database\Query\Builder
 */
class QueryBuilder extends Builder
{
    use BuilderCacheTrait;

    
    /**
     * @override
     * Выполняет запрос как оператор «select»
     * @see parent::get()
     *
     * @param  array|string  $columns
     * @return \Illuminate\Support\Collection<int, \stdClass>
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

        dd($this);
        $result = parent::first($columns);

        //?!? сохранить кеш

        return $result;
    }
}
