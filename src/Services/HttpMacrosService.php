<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Http\Client\PendingRequest;
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
        if (Lh::config(ConfigEnum::HttpCache, 'enabled')) {
            // Регистрация макроса кеширования http запроса
            $withHttpCacheMacro = function (int|string|bool|null $seconds = null) {
                /** @var PendingRequest $this */
                return Lh::config(ConfigEnum::Macros, 'http.enabled')
                    && Lh::config(ConfigEnum::HttpCache, 'enabled')
                    ? app(HttpCacheService::class)->setMacro($this, $seconds) //?!? seconds проверить
                    : $this;
            };
            PendingRequest::macro('withCache', $withHttpCacheMacro);
            PendingRequest::macro('withHttpCache', $withHttpCacheMacro);
        }

        if (Lh::config(ConfigEnum::Macros, 'http.enabled')) {
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
                        // Важно: timeout=0 отключает таймаут в Guzzle/cURL и может подвесить job до таймаута воркера.
                        // Поэтому гарантируем ненулевое значение.
                        ->timeout(
                            ($timeout = (int)Lh::config(ConfigEnum::Http, 'telegramOrg.timeout')) > 0
                            ? $timeout
                            : 10
                        )
                        ->connectTimeout(
                            ($connectionTimeout = (int)Lh::config(ConfigEnum::Http, 'telegramOrg.connection_timeout')) > 0
                            ? $connectionTimeout
                            : ($timeout > 0 ? $timeout : 10)
                        )
                        ->withOptions([
                            'force_ip_resolve' => CURL_IPRESOLVE_V4, // <- форсируем IPv4
                            'headers'          => [
                                'Connection' => 'keep-alive',
                            ],
                        ])
                        ->retry(5, 300)
                );
        }
    }
}
