<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum QueueLogStatusEnum: string
{
    use HelperEnumTrait;


    case Wait = 'wait';
    case Process = 'process';
    case Success = 'success';
    case Failed = 'failed';
    case Exception = 'exception';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::Wait->value;
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
            self::Wait => 'В ожидании',
            self::Process => 'В процессе',
            self::Success => 'Успешно',
            self::Failed => 'Неудачно',
            self::Exception => 'Исключение',

            default => null,
        };
    }
}
