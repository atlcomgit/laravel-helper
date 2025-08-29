<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum TelegramBotMessageTypeEnum: string
{
    use HelperEnumTrait;


    case Incoming = 'incoming';
    case Outgoing = 'outgoing';


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
            self::Incoming => 'Входящее сообщение',
            self::Outgoing => 'Исходящее сообщение',

            default => null,
        };
    }
}
