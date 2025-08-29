<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum ApplicationTypeEnum: string
{
    use HelperEnumTrait;


    case Command = 'command';
    case Http = 'http';
    case Queue = 'queue';
    case Testing = 'testing';


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
            self::Command => 'Консольная команда',
            self::Http => 'Http запрос',
            self::Queue => 'Очередь',
            self::Testing => 'Тестирование',

            default => null,
        };
    }
}
