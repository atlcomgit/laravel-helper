<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;

/**
 * Трейт для подключения соединений к базе данных
 * 
 * @mixin \Illuminate\Database\Connection
 */
trait ConnectionTrait
{
    /**
     * @override
     * Возвращает новый экземпляр QueryBuilder
     * @see parent::query()
     *
     * @return \Illuminate\Database\Query\Builder
     */
    // #[Override()]
    public function query()
    {
        return new QueryBuilder($this, $this->getQueryGrammar(), $this->getPostProcessor());
    }


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
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        //?!? проверить кеш

        $result = parent::select($query, $bindings, $useReadPdo);

        //?!? сохранить кеш

        return $result;
    }


    /**
     * @override
     * Выполняет оператор UPDATE в базе данных
     * @see parent::update()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    // #[Override()]
    public function update($query, $bindings = [])
    {
        $result = parent::update($query, $bindings);

        //?!? сбросить кеш

        return $result;
    }


    /**
     * @override
     * Выполняет оператор DELETE в базе данных
     * @see parent::delete()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    // #[Override()]
    public function delete($query, $bindings = [])
    {
        $result = parent::delete($query, $bindings);

        //?!? сбросить кеш

        return $result;
    }


    /**
     * @override
     * Выполняет оператор сырого SQL и возвращает логический результат
     * @see parent::statement()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    // #[Override()]
    public function statement($query, $bindings = [])
    {
        //?!? если есть SELECT то проверить кеш

        $result = parent::statement($query, $bindings);

        //?!? если есть INSERT, UPDATE, DELETE то сбросить кеш

        return $result;
    }
}
