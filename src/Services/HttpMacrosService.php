<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Support\Facades\Http;

/**
 * Сервис регистрации http макросов
 */
class HttpMacrosService extends DefaultService
{
    /**
     * Добавляет макросы в http запросы
     *
     * @return void
     */
    public static function setMacros(): void
    {
        // Регистрация макроса запроса к localhost
        !Lh::config(ConfigEnum::Http, 'localhost.enabled')
            ?: Http::macro(
                'localhost',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'localhost.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::Localhost))
            );

        // Регистрация макроса запроса к sms.ru
        !Lh::config(ConfigEnum::Http, 'smsRu.enabled')
            ?: Http::macro(
                'smsRu',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'smsRu.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::SmsRu))
            );

        // Регистрация макроса запроса к mango-office.ru
        !Lh::config(ConfigEnum::Http, 'mangoOfficeRu.enabled')
            ?: Http::macro(
                'mangoOfficeRu',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'mangoOfficeRu.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::MangoOfficeRu))
                    ->withOptions([
                        'curl' => [CURLOPT_FOLLOWLOCATION => true],
                    ])
                    ->withoutVerifying()
                    ->asMultipart()
                    ->acceptJson()
                    ->timeout(30)
            );

        // Регистрация макроса запроса к devline.ru
        !Lh::config(ConfigEnum::Http, 'devlineRu.enabled')
            ?: Http::macro(
                'devlineRu',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'devlineRu.url.http'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::DevlineRu))
                    ->asJson()
                    ->acceptJson()
                    ->timeout(Lh::config(ConfigEnum::Http, 'devlineRu.timeout'))
            );

        // Регистрация макроса запроса к rtsp.me
        !Lh::config(ConfigEnum::Http, 'rtspMe.enabled')
            ?: Http::macro(
                'rtspMe',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'rtspMe.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::RtspMe))
                    ->asForm()
                    ->acceptJson()
                    ->timeout(Lh::config(ConfigEnum::Http, 'rtspMe.timeout'))
            );

        // Регистрация макроса запроса к fcm.googleapis.com
        !Lh::config(ConfigEnum::Http, 'fcmGoogleApisCom.enabled')
            ?: Http::macro(
                'fcmGoogleApisCom',
                function () {
                    $client = new \Google\Client();
                    $client->setAuthConfig(Lh::config(ConfigEnum::Http, 'fcmGoogleApisCom.firebase_credentials'));
                    $client->addScope(\Google\Service\FirebaseCloudMessaging::CLOUD_PLATFORM);
                    $token = $client->fetchAccessTokenWithAssertion()['access_token'];
                    $projectId = Lh::config(ConfigEnum::Http, 'fcmGoogleApisCom.project_id');

                    return Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'fcmGoogleApisCom.url'), '/') . "/projects/{$projectId}")
                        ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::FcmGoogleApisCom))
                        ->withToken($token)
                        ->asJson()
                        ->acceptJson()
                        ->timeout(Lh::config(ConfigEnum::Http, 'fcmGoogleApisCom.timeout'));
                }
            );

        // Регистрация макроса запроса к api.telegram.org
        !Lh::config(ConfigEnum::Http, 'telegramOrg.enabled')
            ?: Http::macro(
                'telegramOrg',
                fn () => Http::baseUrl(rtrim(Lh::config(ConfigEnum::Http, 'telegramOrg.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::TelegramOrg))
                    ->asMultipart()
                    ->acceptJson()
                    ->timeout(Lh::config(ConfigEnum::Http, 'telegramOrg.timeout'))
                    ->connectTimeout(Lh::config(ConfigEnum::Http, 'telegramOrg.timeout'))
            );
    }
}
