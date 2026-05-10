<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Стили inline и reply кнопок Telegram.
 */
enum TelegramBotButtonStyleEnum: string
{
    use HelperEnumTrait;


    case Danger  = 'danger';
    case Success = 'success';
    case Primary = 'primary';


    /**
     * Возвращает стиль по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Primary->value;
    }


    /**
     * Возвращает описание стиля.
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Danger => 'Опасность',
            self::Success => 'Успех',
            self::Primary => 'Основной',

            default => null,
        };
    }
}
