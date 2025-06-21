<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Трейт для подключения динамических таблиц к модели
 */
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


    /**
     * Возвращает конструктор запроса с указанием соединения
     *
     * @param string $connection
     * @param string $table
     * @return Builder
     */
    public static function queryFrom(string $connection, string $table): Builder
    {
        return (new static())->setConnection($connection)->setTable($table)->newQuery();
    }


    /**
     * Возвращает конструктор запроса
     * @see \Illuminate\Database\Eloquent\Model::query()
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function query()
    {
        return match (static::class) {
            ConsoleLog::class => static::queryFrom(
                connection: config('laravel-helper.console_log.connection'),
                table: config('laravel-helper.console_log.table'),
            ),

            HttpLog::class => static::queryFrom(
                connection: config('laravel-helper.http_log.connection'),
                table: config('laravel-helper.http_log.table'),
            ),

            ModelLog::class => static::queryFrom(
                connection: config('laravel-helper.model_log.connection'),
                table: config('laravel-helper.model_log.table'),
            ),

            QueryLog::class => static::queryFrom(
                connection: config('laravel-helper.query_log.connection'),
                table: config('laravel-helper.query_log.table'),
            ),

            QueueLog::class => static::queryFrom(
                connection: config('laravel-helper.queue_log.connection'),
                table: config('laravel-helper.queue_log.table'),
            ),

            RouteLog::class => static::queryFrom(
                connection: config('laravel-helper.route_log.connection'),
                table: config('laravel-helper.route_log.table'),
            ),

            ViewLog::class => static::queryFrom(
                connection: config('laravel-helper.view_log.connection'),
                table: config('laravel-helper.view_log.table'),
            ),

            default => parent::query(),
        };
    }
}
