<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum TelegramTypeEnum: string
{
    use HelperEnumTrait;


    case Info = 'info';
    case Error = 'error';
    case Warning = 'warning';
    case Notice = 'notice';
    case Debug = 'debug';
    case None = 'none';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Debug->value;
    }


    /**
     * Возвращает описание ключей.
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Info => 'Информация',
            self::Error => 'Ошибка',
            self::Warning => 'Предупреждение',
            self::Notice => 'Уведомление',
            self::Debug => 'Отладка',
            self::None => 'Без отправки',

            default => null,
        };
    }
}
