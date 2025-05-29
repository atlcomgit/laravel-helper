<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Сервис api telegram
 */
class TelegramApiService
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
     * @return void
     */
    public function checkResponse(Response $response): void
    {
        $json = $response->json();

        ($response->successful() && ($json['ok'] ?? false) === true)
            ?: throw new WithoutTelegramException(
                "Ошибка отправки сообщения в телеграм: " . ($json['description'] ?? $response->getReasonPhrase()),
                $json['error_code'] ?? $response->getStatusCode() ?? 400,
            );
    }


    /**
     * Отправляет сообщение в Telegram
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $chatId   ID чата или username (например, @username)
     * @param string $message  Текст сообщения
     * @param array  $options  Дополнительные параметры (например, parse_mode)
     * @return array|null      Ответ Telegram API или null при ошибке
     */
    public function sendMessage(
        string $botToken,
        string $chatId,
        string $message,
        array $options = [],
    ): ?array {
        $response = $this->getHttp()->post("bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
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
     * @param string $message  Текст сообщения (caption)
     * @param array  $options  Дополнительные параметры (например, parse_mode)
     * @return array|null      Ответ Telegram API или null при ошибке
     */
    public function sendMessageWithFile(
        string $botToken,
        string $chatId,
        string $filePath,
        string $message,
        array $options = [],
    ): ?array {
        file_exists($filePath)
            ?: throw new WithoutTelegramException("Ошибка отправки сообщения в телеграм: Файл не найден {$filePath}");

        $response = $this->getHttp()
            ->attach('document', file_get_contents($filePath), basename($filePath))
            ->post("bot{$botToken}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => $message,
                'parse_mode' => 'HTML',
                ...$options,
            ]);

        return $this->checkResponse($response);
    }
}
