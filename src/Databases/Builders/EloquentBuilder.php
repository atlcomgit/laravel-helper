<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Builders;

use Atlcom\LaravelHelper\Traits\QueryTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Переопределенный конструктор запросов Query Builder
 * 
 * @template TModel of Model
 * @template TValue
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class EloquentBuilder extends Builder
{
    use QueryTrait;


    /**
     * @override
     * Выполняет запрос как оператор «select» с использованием кеша
     * @see parent::get()
     *
     * @param  array|string  $columns
     * @return Collection<int, TModel>
     */
    // #[Override()]
    public function get($columns = ['*']): Collection
    {
        return $this->queryGet($columns);
    }


    //?!? +
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
     * @return mixed
     */
    // #[Override()]
    public function delete()
    {
        return $this->queryDelete();
    }
}
