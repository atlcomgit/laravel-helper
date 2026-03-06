<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Support\Facades\Http;

/**
 * Сервис верификации Google reCAPTCHA v3
 */
class RecaptchaService extends DefaultService
{
    /**
     * Проверяет включена ли reCAPTCHA
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)Lh::config(ConfigEnum::Http, 'googleRecaptchaCom.enabled')
            && (string)Lh::config(ConfigEnum::Http, 'googleRecaptchaCom.secret_key') !== '';
    }


    /**
     * Возвращает публичный ключ reCAPTCHA (site key)
     *
     * @return string
     */
    public function getSiteKey(): string
    {
        return (string)Lh::config(ConfigEnum::Http, 'googleRecaptchaCom.site_key');
    }


    /**
     * Возвращает минимальный допустимый score
     *
     * @return float
     */
    public function getScore(): float
    {
        return (float)Lh::config(ConfigEnum::Http, 'googleRecaptchaCom.score');
    }


    /**
     * Верифицирует токен reCAPTCHA через Google API
     *
     * @param string $token
     * @param string|null $remoteIp
     * @return bool
     */
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        // Если reCAPTCHA отключена — пропускаем верификацию
        !$this->isEnabled() ?: null;
        if (!$this->isEnabled()) {
            return true;
        }

        $secretKey = (string)Lh::config(ConfigEnum::Http, 'googleRecaptchaCom.secret_key');
        $minScore = $this->getScore();

        // Параметры запроса к Google reCAPTCHA API
        $params = [
            'secret'   => $secretKey,
            'response' => $token,
        ];

        // Добавляем IP клиента если передан
        $remoteIp !== null ? $params['remoteip'] = $remoteIp : null;

        try {
            // Отправка запроса через HTTP макрос
            $response = Http::googleRecaptchaCom()
                ->post('/siteverify', $params);

            $data = $response->json();

            // Проверка успешности и score
            return !empty($data['success'])
                && isset($data['score'])
                && (float)$data['score'] >= $minScore;
        } catch (\Throwable) {
            return false;
        }
    }
}
