<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Сервис api telegram
 */
class TelegramApiService extends DefaultService
{
    /**
     * Возвращает фасад запроса
     *
     * @return Http|PendingRequest
     */
    public function getHttp(): Http|PendingRequest
    {
        return Http::telegramOrg();
    }


    /**
     * Проверяет ответ на успех
     *
     * @param Response $response
     * @return mixed
     */
    public function checkResponse(Response $response): mixed
    {
        $json = $response->json();

        return ($response->successful() && ($json['ok'] ?? false) === true)
            ? $json
            : throw new WithoutTelegramException(
                "Ошибка отправки сообщения в телеграм: " . ($json['description'] ?? $response->getReasonPhrase()),
                $json['error_code'] ?? $response->getStatusCode() ?? 400,
            );
    }


    /**
     * Отправляет сообщение в Telegram
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $chatId   ID чата или username (например, @username)
     * @param string $text     Текст сообщения
     * @param string $parseMode
     * @param array  $options  Дополнительные параметры (например, parse_mode)
     * @return mixed           Ответ Telegram API или null при ошибке
     */
    public function sendMessage(
        string $botToken,
        string $chatId,
        string $text,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $response = $this->getHttp()->post("bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            ...$options,
        ]);

        return $this->checkResponse($response);
    }


    /**
     * Отправляет сообщение с файлом в начале сообщения
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $chatId   ID чата или username (например, @username)
     * @param string $filePath Путь к файлу для отправки
     * @param string $caption  Текст сообщения (caption)
     * @param string $parseMode
     * @param array  $options  Дополнительные параметры (например, parse_mode)
     * @return mixed           Ответ Telegram API или null при ошибке
     */
    public function sendMessageWithFile(
        string $botToken,
        string $chatId,
        string $filePath,
        string $caption,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        file_exists($filePath)
            ?: throw new WithoutTelegramException("Ошибка отправки сообщения в телеграм: Файл не найден {$filePath}");

        $response = $this->getHttp()
            ->attach('document', file_get_contents($filePath), basename($filePath))
            ->post("bot{$botToken}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => $parseMode,
                ...$options,
            ]);

        return $this->checkResponse($response);
    }


    public function setWebhook(string $botToken, string $url, array $options = []): mixed
    {
        $response = $this->getHttp()->post("bot{$botToken}/setWebhook", [
            'url' => $url,
            ...$options,
        ]);

        return $this->checkResponse($response);
    }
}
