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
    case MangoOfficeRu = 'mango-office.ru';
    case DevlineRu = 'devline.ru';
    case RtspMe = 'rtsp.me';
    case FcmGoogleApisCom = 'fcm-google-apis.com';
    case TelegramOrg = 'api.telegram.org';


    /**
     * Возвращает вариант enum по умолчанию.
     *
     * @return mixed
     */
    public static function getDefault(): mixed
    {
        return self::Unknown->value;
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
            self::None => 'Отключение лога ',
            self::Unknown => 'Неизвестный запрос',
            self::SmsRu => 'Запрос на отправку смс сообщений',
            self::MangoOfficeRu => 'Сервис звонков',
            self::DevlineRu => 'Сервис видео-камер',
            self::RtspMe => 'Сервис rtsp потока',
            self::FcmGoogleApisCom => 'Сервис fcm google apis',
            self::TelegramOrg => 'Сервис telegram api',

            default => null,
        };
    }
}
