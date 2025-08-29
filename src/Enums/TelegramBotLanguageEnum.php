<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Языки для language_code Telegram команд
 */
enum TelegramBotLanguageEnum: string
{
    use HelperEnumTrait;

    case Ru = 'ru';
    case En = 'en';
    case Uk = 'uk';
    case De = 'de';

    public static function getDefault(): mixed
    {
        return self::En->value;
    }


    public static function getLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Ru => 'Русский',
            self::En => 'Английский',
            self::Uk => 'Украинский',
            self::De => 'Немецкий',
            default => null,
        };
    }
}
