<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Тип направления сортировки
 */
enum SortDirectionEnum: string
{
    use HelperEnumTrait;


    case Asc = 'asc';
    case Desc = 'desc';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Asc;
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
            self::Asc => 'Сортировка по возрастанию',
            self::Desc => 'Сортировка по убыванию',

            default => null,
        };
    }
}
