<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum EventTypeEnum: string
{
    use HelperEnumTrait;


    case GetHttpCache = 'get_http_cache';
    case SetHttpCache = 'set_http_cache';
    case ClearHttpCache = 'clear_http_cache';
    case GetQueryCache = 'get_query_cache';
    case SetQueryCache = 'set_query_cache';
    case ClearQueryCache = 'clear_query_cache';
    case GetViewCache = 'get_view_cache';
    case SetViewCache = 'set_view_cache';
    case ClearViewCache = 'clear_view_cache';


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
            self::GetHttpCache => 'Получение http кеша',
            self::SetHttpCache => 'Сохранение http кеша',
            self::ClearHttpCache => 'Сброс http кеша',
            self::GetQueryCache => 'Получение query кеша',
            self::SetQueryCache => 'Сохранение query кеша',
            self::ClearQueryCache => 'Сброс query кеша',
            self::GetViewCache => 'Получение view кеша',
            self::SetViewCache => 'Сохранение view кеша',
            self::ClearViewCache => 'Сброс view кеша',

            default => null,
        };
    }
}
