<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum ConfigEnum: string
{
    use HelperEnumTrait;


    case App = 'app';
    case Optimize = 'optimize';
    case Macros = 'macros';
    case ConsoleLog = 'console_log';
    case HttpLog = 'http_log';
    case ModelLog = 'model_log';
    case QueryCache = 'query_cache';
    case QueryLog = 'query_log';
    case QueueLog = 'queue_log';
    case RouteLog = 'route_log';
    case TelegramLog = 'telegram_log';
    case TestingLog = 'testing_log';
    case ViewCache = 'view_cache';
    case ViewLog = 'view_log';
    case Http = 'http';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return null;
    }


    /**
     * Возвращает описание ключей.
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function getLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::App => 'Приложение',
            self::Optimize => 'Оптимизация',
            self::Macros => 'Макрос',
            self::ConsoleLog => 'Лог консольной команды',
            self::HttpLog => 'Лог http запроса',
            self::ModelLog => 'Лог изменения модели',
            self::QueryCache => 'Кеш query запроса',
            self::QueryLog => 'Лог query запроса',
            self::QueueLog => 'Лог очереди',
            self::RouteLog => 'Лог зарегистрированного роута',
            self::TelegramLog => 'Лог телеграм сообщения',
            self::TestingLog => 'Лог тестирования',
            self::ViewCache => 'Кеш blade шаблона',
            self::ViewLog => 'Лог blade шаблона',
            self::Http => 'Макрос http',

            default => null,
        };
    }


    /**
     * Возвращает описание ключа
     *
     * @return string|null
     */
    public function label(): ?string
    {
        return self::getLabel($this);
    }
}
