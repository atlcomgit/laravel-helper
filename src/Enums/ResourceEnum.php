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
    case Form = 'form';
    case FormCreate = 'form_create';
    case FormUpdate = 'form_update';
    case Create = 'create';
    case Read = 'read';
    case Update = 'update';
    case Delete = 'delete';
    case Patch = 'patch';
    case Short = 'short';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Default;
    }


    /**
     * Возвращает описание ключей
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Default => 'Ресурс по умолчанию',
            self::Index => 'Ресурс для таблицы',
            self::Form => 'Ресурс для формы записи',
            self::FormCreate => 'Ресурс для формы добавления записи',
            self::FormUpdate => 'Ресурс для формы обновления записи',
            self::Create => 'Ресурс для создания записи',
            self::Read => 'Ресурс для чтения записи',
            self::Update => 'Ресурс для обновления записи',
            self::Delete => 'Ресурс для удаления записи',
            self::Patch => 'Ресурс для изменения свойства записи',
            self::Short => 'Ресурс для укороченной записи',

            default => null,
        };
    }
}
