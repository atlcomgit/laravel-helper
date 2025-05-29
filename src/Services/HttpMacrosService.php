<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Illuminate\Support\Facades\Http;

/**
 * Сервис регистрации http макросов
 */
class HttpMacrosService
{
    public static function setMacros(): void
    {
        // Регистрация макроса запроса в sms.ru
        !config('laravel-helper.http.smsRu.enabled')
            ?: Http::macro(
                'smsRu',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.smsRu.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::SmsRu))
            );

        // Регистрация макроса запроса в mango-office.ru
        !config('laravel-helper.http.mangoOfficeRu.enabled')
            ?: Http::macro(
                'mangoOfficeRu',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.mangoOfficeRu.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::MangoOfficeRu))
                    ->withOptions([
                        'curl' => [CURLOPT_FOLLOWLOCATION => true],
                    ])
                    ->withoutVerifying()
                    ->asMultipart()
                    ->acceptJson()
                    ->timeout(30)
            );

        // Регистрация макроса запроса в devline.ru
        !config('laravel-helper.http.devlineRu.enabled')
            ?: Http::macro(
                'devlineRu',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.devlineRu.url.http'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::DevlineRu))
                    ->asJson()
                    ->acceptJson()
                    ->timeout(config('laravel-helper.http.devlineRu.timeout'))
            );

        // Регистрация макроса запроса в rtsp.me
        !config('laravel-helper.http.rtspMe.enabled')
            ?: Http::macro(
                'rtspMe',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.rtspMe.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::RtspMe))
                    ->asForm()
                    ->acceptJson()
                    ->timeout(config('laravel-helper.http.rtspMe.timeout'))
            );

        // Регистрация макроса запроса в fcm.googleapis.com
        !config('laravel-helper.http.fcmGoogleApisCom.enabled')
            ?: Http::macro(
                'fcmGoogleApisCom',
                function () {
                    $client = new \Google\Client();
                    $client->setAuthConfig(config('laravel-helper.http.fcmGoogleApisCom.firebase_credentials'));
                    $client->addScope(\Google\Service\FirebaseCloudMessaging::CLOUD_PLATFORM);
                    $token = $client->fetchAccessTokenWithAssertion()['access_token'];
                    $projectId = config('laravel-helper.http.fcmGoogleApisCom.project_id');

                    return Http::baseUrl(rtrim(config('laravel-helper.http.fcmGoogleApisCom.url'), '/') . "/projects/{$projectId}")
                        ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::FcmGoogleApisCom))
                        ->withToken($token)
                        ->asJson()
                        ->acceptJson()
                        ->timeout(config('laravel-helper.http.fcmGoogleApisCom.timeout'));
                }
            );

        // Регистрация макроса запроса в api.telegram.org
        !config('laravel-helper.http.telegramOrg.enabled')
            ?: Http::macro(
                'telegramOrg',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.telegramOrg.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::TelegramOrg))
                    ->asMultipart()
                    ->acceptJson()
                    ->timeout(config('laravel-helper.http.telegramOrg.timeout'))
                    ->connectTimeout(config('laravel-helper.http.telegramOrg.timeout'))
            );
    }
}
