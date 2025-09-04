<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Hlp;
use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum TelegramBotVariableTypeEnum: string
{
    use HelperEnumTrait;


    case Null = 'null';
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
            self::Null => 'Null значение',
            self::Boolean => 'Логическое значение',
            self::Integer => 'Целое значение',
            self::Float => 'Вещественное значение',
            self::String => 'Строковое значение',
            self::Array => 'Массив',
            self::Object => 'Объект',

            default => null,
        };
    }


    public static function getType(mixed $value): ?static
    {
        return match (true) {
            is_null($value) => TelegramBotVariableTypeEnum::Null,
            is_bool($value) => TelegramBotVariableTypeEnum::Boolean,
            is_integer($value) => TelegramBotVariableTypeEnum::Integer,
            is_float($value) => TelegramBotVariableTypeEnum::Float,
            is_string($value) => TelegramBotVariableTypeEnum::String,
            is_array($value) => TelegramBotVariableTypeEnum::Array ,
            is_object($value) => TelegramBotVariableTypeEnum::Object,

            default => null,
        };
    }


    public static function getValue(self $type, mixed $value): mixed
    {
        return match ($type) {
            self::Null => null,
            self::Boolean => Hlp::castToBool($value),
            self::Integer => Hlp::castToInt($value),
            self::Float => Hlp::castToFloat($value),
            self::String => Hlp::castToString($value),
            self::Array => Hlp::castToArray($value),
            self::Object => Hlp::castToObject($value),

            default => class_exists($type->value) ? new $type->value($value) : $value,
        };
    }
}
