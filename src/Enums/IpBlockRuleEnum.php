<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Enum правил блокировки ip адресов
 */
enum IpBlockRuleEnum: string
{
    use HelperEnumTrait;


    case RequestsPerMinute     = 'requests_per_minute';
    case NotFoundPerMinute     = 'not_found_per_minute';
    case UnauthorizedPerMinute = 'unauthorized_per_minute';
    case SuspiciousPayload     = 'suspicious_payload';
    case ManualBlock           = 'manual_block';


    /**
     * Возвращает вариант enum по умолчанию
     *
     * @return mixed
     */
    public static function enumDefault(): mixed
    {
        return self::RequestsPerMinute;
    }


    /**
     * Возвращает описание ключей
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::RequestsPerMinute => 'Лимит запросов в минуту',
            self::NotFoundPerMinute => 'Лимит 404 в минуту',
            self::UnauthorizedPerMinute => 'Лимит неавторизованных запросов',
            self::SuspiciousPayload => 'Блокировка по подозрительному payload',
            self::ManualBlock => 'Ручная блокировка',

            default => null,
        };
    }
}
