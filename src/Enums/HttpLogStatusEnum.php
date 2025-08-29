<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum HttpLogStatusEnum: string
{
    use HelperEnumTrait;


    case Process = 'process';
    case Success = 'success';
    case Failed = 'failed';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Process->value;
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
            self::Process => 'В процессе',
            self::Success => 'Успешно',
            self::Failed => 'Ошибка',

            default => null,
        };
    }
}
