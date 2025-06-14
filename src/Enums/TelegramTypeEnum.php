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
    case Debug = 'debug';
    case None = 'none';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Debug->value;
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
            self::Info => 'Информация',
            self::Error => 'Ошибка',
            self::Warning => 'Предупреждение',
            self::Debug => 'Отладка',
            self::None => 'Без отправки',

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
