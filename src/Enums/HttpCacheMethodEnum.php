<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum HttpCacheMethodEnum: string
{
    use HelperEnumTrait;


    case Get = 'GET';
    case Post = 'POST';


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
            self::Get => 'GET запрос',
            self::Post => 'POST запрос',

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
