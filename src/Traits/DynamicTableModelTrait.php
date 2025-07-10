<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Defaults\DefaultTest;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Models\ViewLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
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
     * @param ConfigEnum $config
     * @return Builder
     */
    public static function queryFrom(ConfigEnum $config): Builder
    {
        return (new static())
            ->setConnection(LaravelHelperService::getConnection($config))
            ->setTable(LaravelHelperService::getTable($config))
            ->newQuery();
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
            ConsoleLog::class => static::queryFrom(ConfigEnum::ConsoleLog),
            HttpLog::class => static::queryFrom(ConfigEnum::HttpLog),
            ModelLog::class => static::queryFrom(ConfigEnum::ModelLog),
            ProfilerLog::class => static::queryFrom(ConfigEnum::ProfilerLog),
            RouteLog::class => static::queryFrom(ConfigEnum::RouteLog),
            QueryLog::class => static::queryFrom(ConfigEnum::QueryLog),
            QueueLog::class => static::queryFrom(ConfigEnum::QueueLog),
            ViewLog::class => static::queryFrom(ConfigEnum::ViewLog),

            default => parent::query(),
        };
    }
}
