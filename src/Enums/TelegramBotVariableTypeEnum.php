<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum TelegramBotVariableTypeEnum: string
{
    use HelperEnumTrait;


    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case String = 'string';
    case Array = 'array';
    case Object = 'object';


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
            self::Boolean => 'Логическое значение',
            self::Integer => 'Целое значение',
            self::Float => 'Вещественное значение',
            self::String => 'Строковое значение',
            self::Array => 'Массив',
            self::Object => 'Объект',

            default => null,
        };
    }
}
