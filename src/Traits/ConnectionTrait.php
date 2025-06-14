<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Throwable;
use Atlcom\LaravelHelper\Traits\QueryTrait;

/**
 * Трейт для подключения соединений к базе данных
 * 
 * @mixin \Illuminate\Database\Connection|QueryTrait
 */
trait ConnectionTrait
{
    use QueryTrait;


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
        return $this->querySelect($query, $bindings, $useReadPdo);
    }


    /**
     * @override
     * Выполняет оператор INSERT в базе данных
     * @see parent::insert()
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  string|null $sequence
     * @return int|bool
     */
    // #[Override()]
    public function insert($query, $bindings = [], $sequence = null)
    {
        return $this->queryInsert($query, $bindings);
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
        return $this->queryUpdate($query, $bindings);
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
        return $this->queryDelete(query: $query, bindings: $bindings, isSoftDelete: false);
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
        try {
            $status = false;
            $arrayQueryLogDto = $this->createQueryLog(sql($query, $bindings));
            $result = parent::statement($query, $bindings);
            !($result && Hlp::sqlHasWrite($query)) ?: $this->flushCache($query, $bindings);
            $status = true;

        } catch (Throwable $exception) {
            throw $exception;
        }

        $this->updateQueryLog(arrayQueryLogDto: $arrayQueryLogDto, result: $result, status: $status);

        return $result;
    }
}
