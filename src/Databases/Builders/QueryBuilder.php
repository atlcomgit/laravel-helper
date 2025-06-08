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

    //?!? 
    /**
     * @override
     * Выполняет оператор SELECT в базе данных
     * @see parent::select()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    // #[Override()]
    // public function select($columns = ['*'])
    // {
    //     return $this->querySelect($query, $bindings);
    // }


    /**
     * @override
     * Вставляет новые записи в базу данных
     * @see parent::insert()
     *
     * @return bool
     */
    // #[Override()]
    public function insert(array $values)
    {
        return $this->queryInsert($values);
    }


    /**
     * @override
     * Обновляет записи в базе данных
     * @see parent::update()
     *
     * @param  array  $values
     * @return int
     */
    // #[Override()]
    public function update(array $values)
    {
        return $this->queryUpdate($values);
    }


    /**
     * @override
     * Удаляет записи из базы данных
     * @see parent::delete()
     *
     * @param  mixed $id
     * @return mixed
     */
    // #[Override()]
    public function delete($id = null)
    {
        return $this->queryDelete($id);
    }
}
