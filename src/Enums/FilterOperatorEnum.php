<?php

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Список операторов фильтра
 */
enum FilterOperatorEnum: string
{
    use HelperEnumTrait;


    case Equal = 'equal';
    case Like = 'like';
    case Ilike = 'ilike';
    case In = 'in';
    case Between = 'between';
    case Closure = 'closure';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Equal;
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
            self::Equal => 'Равенство',
            self::Like => 'Содержание',
            self::Ilike => 'Содержание без учета регистра',
            self::In => 'Список',
            self::Between => 'В интервале',
            self::Closure => 'Замыкание конструктора запроса',

            default => null,
        };
    }
}
