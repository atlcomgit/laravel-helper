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
    case Http = 'http';
    case ConsoleLog = 'console_log';
    case HttpCache = 'http_cache';
    case HttpLog = 'http_log';
    case ModelLog = 'model_log';
    case ProfilerLog = 'profiler_log';
    case RouteLog = 'route_log';
    case QueryCache = 'query_cache';
    case QueryLog = 'query_log';
    case QueueLog = 'queue_log';
    case TelegramBot = 'telegram_bot';
    case TelegramLog = 'telegram_log';
    case TestingLog = 'testing_log';
    case ViewCache = 'view_cache';
    case ViewLog = 'view_log';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return null;
    }


    /**
     * Возвращает описание ключей.
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::App => 'Приложение',
            self::Optimize => 'Оптимизация',
            self::Macros => 'Макрос',
            self::Http => 'Макрос http',
            self::ConsoleLog => 'Лог консольной команды',
            self::HttpCache => 'Кеш http запроса',
            self::HttpLog => 'Лог http запроса',
            self::ModelLog => 'Лог изменения модели',
            self::ProfilerLog => 'Лог профилировщика',
            self::RouteLog => 'Лог зарегистрированного роута',
            self::QueryCache => 'Кеш query запроса',
            self::QueryLog => 'Лог query запроса',
            self::QueueLog => 'Лог очереди',
            self::TelegramBot => 'Бот телеграм',
            self::TelegramLog => 'Лог телеграм сообщения',
            self::TestingLog => 'Лог тестирования',
            self::ViewCache => 'Кеш blade шаблона',
            self::ViewLog => 'Лог blade шаблона',

            default => null,
        };
    }
}
