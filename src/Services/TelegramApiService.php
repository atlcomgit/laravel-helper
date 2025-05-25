<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use CURLFile;

class TelegramApiService
{
    /**
     * Отправляет сообщение в Telegram.
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
        Http::telegram(); //?!? 
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $message,
        ], $options);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            // Можно добавить логирование ошибки
            return null;
        }

        $json = json_decode($response, true);

        return ;
    }


    /**
     * Отправляет сообщение с файлом в начале сообщения.
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
        if (!file_exists($filePath)) {
            // Можно добавить логирование ошибки
            return null;
        }

        Http::telegram(); //?!? 

        $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
        $params = array_merge([
            'chat_id' => $chatId,
            'caption' => $message,
            'document' => new CURLFile($filePath),
        ], $options);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            // Можно добавить логирование ошибки
            return null;
        }

        return json_decode($response, true);
    }
}
