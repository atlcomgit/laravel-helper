<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\MailLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Трейт для подключения динамических таблиц к модели
 * 
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultModel
 */
trait DynamicTableModelTrait
{
    protected $connection = null;
    protected $table      = null;


    /**
     * Возвращает конфиг динамической таблицы для текущей модели
     *
     * @return array{0: ConfigEnum, 1: string}|null
     */
    protected static function dynamicBinding(): ?array
    {
        return match (static::class) {
            ConsoleLog::class => [ConfigEnum::ConsoleLog, ''],
            HttpLog::class => [ConfigEnum::HttpLog, ''],
            MailLog::class => [ConfigEnum::MailLog, ''],
            ModelLog::class => [ConfigEnum::ModelLog, ''],
            ProfilerLog::class => [ConfigEnum::ProfilerLog, ''],
            RouteLog::class => [ConfigEnum::RouteLog, ''],
            QueryLog::class => [ConfigEnum::QueryLog, ''],
            QueueLog::class => [ConfigEnum::QueueLog, ''],
            ViewLog::class => [ConfigEnum::ViewLog, ''],
            TelegramBotChat::class => [ConfigEnum::TelegramBot, 'chat'],
            TelegramBotUser::class => [ConfigEnum::TelegramBot, 'user'],
            TelegramBotMessage::class => [ConfigEnum::TelegramBot, 'message'],
            TelegramBotVariable::class => [ConfigEnum::TelegramBot, 'variable'],

            default => null,
        };
    }


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

        $connection = $this->getConnectionName();
        !$connection ?: $model->setConnection($connection);
        $model->setTable($this->getTable());

        return $model;
    }


    /**
     * Возвращает имя соединения с учетом динамической конфигурации
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $binding = static::dynamicBinding();

        return $binding ? Lh::getConnection($binding[0]) : parent::getConnectionName();
    }


    /**
     * Возвращает имя таблицы с учетом динамической конфигурации
     *
     * @return string
     */
    public function getTable()
    {
        if ($this->table !== null) {
            return $this->table;
        }

        $binding = static::dynamicBinding();

        return $binding ? Lh::getTable($binding[0], $binding[1]) : parent::getTable();
    }


    /**
     * Возвращает конструктор запроса с указанием соединения
     *
     * @param ConfigEnum $config
     * @param string $suffix
     * @return Builder
     */
    public static function queryFrom(ConfigEnum $config, string $suffix = ''): Builder
    {
        return (new static())
            ->setConnection(Lh::getConnection($config))
            ->setTable(Lh::getTable($config, $suffix))
            ->newQuery();
    }


    /**
     * Возвращает конструктор запроса
     * @see \Illuminate\Database\Eloquent\Model::query()
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>|static
     */
    public static function query()
    {
        $binding = static::dynamicBinding();

        return $binding
            ? static::queryFrom($binding[0], $binding[1])
            : parent::query();
    }
}
