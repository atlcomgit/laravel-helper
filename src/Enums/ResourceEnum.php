<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Тип ресурса
 */
enum ResourceEnum: string
{
    use HelperEnumTrait;


    case Default = 'default';
    case Index = 'index';
    case Create = 'create';
    case Read = 'read';
    case Update = 'update';
    case Delete = 'delete';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Default;
    }


    /**
     * Возвращает описание ключей
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function getLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Default => 'Ресурс по умолчанию',
            self::Index => 'Ресурс для таблицы',
            self::Create => 'Ресурс для создания записи',
            self::Read => 'Ресурс для чтения записи',
            self::Update => 'Ресурс для обновления записи',
            self::Delete => 'Ресурс для удаления записи',

            default => null,
        };
    }
}
