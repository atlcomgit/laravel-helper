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
    case Localhost = 'localhost';
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
            self::Localhost => 'Сервис localhost',
            self::SmsRu => 'Сервис отправки смс сообщений',
            self::MangoOfficeRu => 'Сервис звонков',
            self::DevlineRu => 'Сервис видео-камер',
            self::RtspMe => 'Сервис rtsp потока',
            self::FcmGoogleApisCom => 'Сервис fcm google apis',
            self::TelegramOrg => 'Сервис telegram api',

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
