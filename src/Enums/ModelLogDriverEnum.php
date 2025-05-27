<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum ModelLogDriverEnum: string
{
    use HelperEnumTrait;


    case Database = 'database';
    case File = 'file';
    case Telegram = 'telegram';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Database->value;
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
            self::Database => 'База данных',
            self::File => 'Файл',
            self::Telegram => 'Телеграм',

            default => null,
        };
    }
}
