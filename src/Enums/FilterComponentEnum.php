<?php

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Список типов фильтра
 */
enum FilterComponentEnum: string
{
    use HelperEnumTrait;


    case Input = 'v-input';
    case Select = 'v-select';
    case ComboboxRadio = 'ComboboxRadio';
    case ComboboxCheck = 'ComboboxCheck';
    case Date = 'v-date-picker';
    case DateInterval = 'DateInterval';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Input;
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
            self::Input => 'Текстовое поле ввода',
            self::Select => 'Выбор одного значения',
            self::ComboboxRadio => 'Выбор одного значений',
            self::ComboboxCheck => 'Выбор нескольких значений',
            self::Date => 'Выбор одной даты',
            self::DateInterval => 'Выбор интервала дат',

            default => null,
        };
    }
}
