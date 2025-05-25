<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрация макросов http запросов
 */
class LaravelHelperHttpServiceProvider extends ServiceProvider
{
    public function register(): void {}


    public function boot()
    {
        // Регистрируем макросы
        $this->registerMacroSmsRu();
        $this->registerMangoOffice();
        $this->registerDevline();
        $this->registerRtspme();
        $this->registerFcmGoogle();
        $this->registerTelegram(); //?!? 

        // Глобальные настройки запросов (required laravel 10 and higher)
        Http::globalOptions([
            'headers' => HttpLogService::getLogHeaders(HttpLogHeaderEnum::Unknown),
            'curl' => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ]);
    }


    /**
     * Регистрация макроса запроса в sms.ru
     *
     * @return void
     */
    private function registerMacroSmsRu(): void
    {
        //?!? 
        if (config('laravel-helper.http.smsru.enabled')) {
            Http::macro(
                'smsRu',
                fn () => Http::baseUrl(rtrim(config('laravel-helper.http.smsru.url'), '/'))
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::SmsRu))
            );
        }
    }


    /**
     * Регистрация макроса запроса в mango-office
     *
     * @return void
     */
    private function registerMangoOffice(): void
    {
        Http::macro(
            'mangoOffice',
            fn () => Http::baseUrl(rtrim(config('mango-office.url'), '/'))
                ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::MangoOffice))
                ->withOptions([
                    'curl' => [CURLOPT_FOLLOWLOCATION => true],
                ])
                ->withoutVerifying()
                ->asMultipart()
                ->acceptJson()
                ->timeout(30)
        );
    }


    /**
     * Регистрация макроса запроса в mango-office
     *
     * @return void
     */
    private function registerDevline(): void
    {
        Http::macro(
            'devline',
            fn () => Http::baseUrl(rtrim(config('devline.url.http'), '/'))
                ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::Devline))
                ->asJson()
                ->acceptJson()
                ->timeout(config('devline.timeout'))
        );
    }


    /**
     * Регистрация макроса запроса в mango-office
     *
     * @return void
     */
    private function registerRtspme(): void
    {
        Http::macro(
            'rtspme',
            fn () => Http::baseUrl(rtrim(config('rtspme.url'), '/'))
                ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::Rtspme))
                ->asForm()
                ->acceptJson()
                ->timeout(config('rtspme.timeout'))
        );
    }


    /**
     * Регистрация макроса запроса в fcm-google
     *
     * @return void
     */
    private function registerFcmGoogle(): void
    {
        Http::macro(
            'fcmGoogle',
            function () {
                $client = new \Google\Client();
                $client->setAuthConfig(config('fcm-google.firebase_credentials'));
                $client->addScope(\Google\Service\FirebaseCloudMessaging::CLOUD_PLATFORM);
                $token = $client->fetchAccessTokenWithAssertion()['access_token'];
                $projectId = config('fcm-google.project_id');

                return Http::baseUrl(rtrim(config('fcm-google.url'), '/') . "/projects/{$projectId}")
                    ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::FcmGoogle))
                    ->withToken($token)
                    ->asJson()
                    ->acceptJson()
                    ->timeout(config('fcm-google.timeout'));
            }
        );
    }
}
