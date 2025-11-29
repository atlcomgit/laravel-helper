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
        return match (static::class) {
            ConsoleLog::class => static::queryFrom(ConfigEnum::ConsoleLog),
            HttpLog::class => static::queryFrom(ConfigEnum::HttpLog),
            MailLog::class => static::queryFrom(ConfigEnum::MailLog),
            ModelLog::class => static::queryFrom(ConfigEnum::ModelLog),
            ProfilerLog::class => static::queryFrom(ConfigEnum::ProfilerLog),
            RouteLog::class => static::queryFrom(ConfigEnum::RouteLog),
            QueryLog::class => static::queryFrom(ConfigEnum::QueryLog),
            QueueLog::class => static::queryFrom(ConfigEnum::QueueLog),
            ViewLog::class => static::queryFrom(ConfigEnum::ViewLog),
            TelegramBotChat::class => static::queryFrom(ConfigEnum::TelegramBot, 'chat'),
            TelegramBotUser::class => static::queryFrom(ConfigEnum::TelegramBot, 'user'),
            TelegramBotMessage::class => static::queryFrom(ConfigEnum::TelegramBot, 'message'),
            TelegramBotVariable::class => static::queryFrom(ConfigEnum::TelegramBot, 'variable'),

            default => parent::query(),
        };
    }
}
