<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum ModelLogTypeEnum: string
{
    use HelperEnumTrait;


    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case SoftDelete = 'soft_delete';
    case ForceDelete = 'force_delete';
    case Restore = 'restore';


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
            self::Create => 'Создано',
            self::Update => 'Обновлено',
            self::Delete => 'Удалено',
            self::SoftDelete => 'Мягко удалено',
            self::ForceDelete => 'Удалено безвозвратно',
            self::Restore => 'Восстановлено',

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
