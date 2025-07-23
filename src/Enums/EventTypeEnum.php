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
    case FlushHttpCache = 'flush_http_cache';
    case GetQueryCache = 'get_query_cache';
    case SetQueryCache = 'set_query_cache';
    case FlushQueryCache = 'flush_query_cache';
    case GetViewCache = 'get_view_cache';
    case SetViewCache = 'set_view_cache';
    case FlushViewCache = 'flush_view_cache';


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
            self::GetHttpCache => 'Получение http кеша',
            self::SetHttpCache => 'Сохранение http кеша',
            self::FlushHttpCache => 'Сброс http кеша',
            self::GetQueryCache => 'Получение query кеша',
            self::SetQueryCache => 'Сохранение query кеша',
            self::FlushQueryCache => 'Сброс query кеша',
            self::GetViewCache => 'Получение view кеша',
            self::SetViewCache => 'Сохранение view кеша',
            self::FlushViewCache => 'Сброс view кеша',

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
