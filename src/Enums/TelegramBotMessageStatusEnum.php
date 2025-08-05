<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum TelegramBotMessageStatusEnum: string
{
    use HelperEnumTrait;


    case New = 'new';
    case Reply = 'reply';
    case Callback = 'callback';
    case Update = 'update';
    case Delete = 'delete';


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
            self::New => 'Новое сообщение',
            self::Reply => 'Ответ на сообщение',
            self::Callback => 'Действие на сообщение',
            self::Update => 'Редактирование сообщения',
            self::Delete => 'Удаление сообщения',

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
