<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

enum HttpLogHeaderEnum: string
{
    use HelperEnumTrait;


    case None = 'none';
    case Unknown = 'unknown';
    case SmsRu = 'sms.ru';
    case MangoOffice = 'mango-office.ru';
    case Devline = 'devline.ru';
    case Rtspme = 'rtsp.me';
    case FcmGoogle = 'fcm-google';
    case Telegram = 'fcm-google';


    /**
     * Возвращает описание ключей.
     *
     * @param BackedEnum|null $enum
     * @return string|null
     */
    public static function getLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::None => 'Отключение лога ',
            self::Unknown => 'Неизвестный запрос',
            self::SmsRu => 'Запрос на отправку смс сообщений',
            self::MangoOffice => 'Сервис звонков',
            self::Devline => 'Сервис видео-камер',
            self::Rtspme => 'Сервис rtsp потока',
            self::FcmGoogle => 'Сервис fcm google apis',
            self::Telegram => 'Сервис telegram api',

            default => null,
        };
    }
}
