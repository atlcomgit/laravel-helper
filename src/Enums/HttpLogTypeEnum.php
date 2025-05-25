<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum HttpLogTypeEnum: string
{
    use HelperEnumTrait;


    case In = 'in';
    case Out = 'out';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Out->value;
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
            self::In => 'Входящий',
            self::Out => 'Исходящий',

            default => null,
        };
    }
}
