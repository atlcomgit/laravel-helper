<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Builders;

use Atlcom\LaravelHelper\Traits\QueryTrait;
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
    use QueryTrait;


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
        return $this->queryGet($columns);
    }
}
