<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

// use Override;

trait DynamicTableModelTrait
{
    protected $connection = null;
    protected $table = null;


    /**
     * Связывает модель с динамической таблицей
     *
     * @param string $connection
     * @param string $table
     * @return void
     */
    public function bind(string $connection, string $table)
    {
        $this->setConnection($connection);
        $this->setTable($table);
    }


    /**
     * Возвращает модель с динамической таблицей
     *
     * @param array $attributes
     * @param bool $exists
     * @return static
     */
    // #[Override()]
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);

        return $model;
    }
}
