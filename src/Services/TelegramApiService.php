<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Exceptions\TelegramBotException;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use CURLFile;
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
    protected function getHttp(): Http|PendingRequest
    {
        return Http::telegramOrg();
    }


    /**
     * Проверяет ответ на успех
     *
     * @param Response $response
     * @return mixed
     */
    protected function checkResponse(Response $response): mixed
    {
        $json = $response->json();

        return ($response->successful() && ($json['ok'] ?? false) === true)
            ? $json
            : match ($description = $json['description'] ?? null) {
                'Bad Request: message to delete not found',
                'Bad Request: message can\'t be deleted for everyone' => $json,

                default => throw new WithoutTelegramException(
                    "Ошибка отправки сообщения в телеграм: " . ($json['description'] ?? $response->getReasonPhrase()),
                    $json['error_code'] ?? $response->getStatusCode() ?? 400,
                ),
            };
    }


    /**
     * Универсальный вызов метода Telegram Bot API
     *
     * @param string $botToken
     * @param string $method
     * @param array  $params
     * @param array  $files [fieldName => "/path/to/file"]
     * @param bool   $json
     * @return mixed
     */
    public function call(
        string $botToken,
        string $method,
        array $params = [],
        array $files = [],
        bool $json = false,
    ): mixed {
        $http = $this->getHttp();
        !$json ?: $http->asJson();

        foreach ($files as $name => $path) {
            if ($path instanceof CURLFile) {
                // Корректная отправка файла: читаем содержимое, используем basename
                $filePath = $path->getFilename();
                if (is_file($filePath)) {
                    $http = $http->attach($name, file_get_contents($filePath), basename($filePath));
                }
            } elseif (is_string($path) && is_file($path)) {
                $http = $http->attach($name, file_get_contents($path), basename($path));
            }
        }

        $response = $http->post("bot{$botToken}/{$method}", $params);

        return $this->checkResponse($response);
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
        return $this->call($botToken, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            ...$options,
        ]);
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
            ?: throw new TelegramBotException("Ошибка отправки сообщения в телеграм: Файл не найден {$filePath}");

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


    /**
     * Устанавливает команды бота (setMyCommands)
     *
     * @param string $botToken  Токен Telegram-бота
     * @param array  $commands  Массив команд вида [["command" => string, "description" => string], ...]
     * @param array  $options   Доп. параметры (scope, language_code и пр.)
     * @return mixed            Ответ Telegram API
     */
    public function setMyCommands(string $botToken, array $commands, array $options = []): mixed
    {
        return $this->call(
            botToken: $botToken,
            method: 'setMyCommands',
            params: [
                'commands' => $commands,
                ...$options,
            ],
            json: true,
        );
    }


    /**
     * Удаляет команды бота (deleteMyCommands)
     *
     * @param string $botToken Токен Telegram-бота
     * @param array  $options  Доп. параметры (scope, language_code)
     * @return mixed           Ответ Telegram API
     */
    public function unsetMyCommands(string $botToken, array $options = []): mixed
    {
        return $this->call(
            botToken: $botToken,
            method: 'deleteMyCommands',
            params: [...$options],
            json: true,
        );
    }


    /**
     * Возвращает команды бота (getMyCommands)
     *
     * @param string $botToken Токен Telegram-бота
     * @param array  $options  Доп. параметры (scope, language_code)
     * @return mixed           Ответ Telegram API
     */
    public function getMyCommands(string $botToken, array $options = []): mixed
    {
        return $this->call($botToken, 'getMyCommands', [
            ...$options,
        ]);
    }


    /**
     * Устанавливает URL вебхука бота (setWebhook)
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $url      Публичный URL для приема обновлений (HTTPS)
     * @param array  $options  Доп. параметры (например: secret_token, certificate, allowed_updates и пр.)
     * @return mixed           Ответ Telegram API
     */
    public function setWebhook(string $botToken, string $url, array $options = []): mixed
    {
        return $this->call($botToken, 'setWebhook', [
            'url' => $url,
            ...$options,
        ]);
    }


    /**
     * Возвращает информацию о боте
     *
     * @param string $botToken Токен Telegram-бота
     * @return mixed           Ответ Telegram API
     */
    public function getMe(string $botToken): mixed
    {
        return $this->call($botToken, 'getMe');
    }


    /**
     * Пересылает сообщение
     *
     * @param string       $botToken   Токен Telegram-бота
     * @param string|int   $chatId     ID чата-получателя
     * @param string|int   $fromChatId ID чата-источника
     * @param int          $messageId  ID сообщения для пересылки
     * @param array        $options    Доп. параметры (disable_notification и др.)
     * @return mixed                   Ответ Telegram API
     */
    public function forwardMessage(string $botToken, string|int $chatId, string|int $fromChatId, int $messageId, array $options = []): mixed
    {
        return $this->call($botToken, 'forwardMessage', [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
            ...$options,
        ]);
    }


    /**
     * Копирует сообщение
     *
     * @param string       $botToken   Токен Telegram-бота
     * @param string|int   $chatId     ID чата-получателя
     * @param string|int   $fromChatId ID чата-источника
     * @param int          $messageId  ID сообщения для копирования
     * @param array        $options    Доп. параметры (caption, parse_mode, reply_markup и др.)
     * @return mixed                   Ответ Telegram API
     */
    public function copyMessage(string $botToken, string|int $chatId, string|int $fromChatId, int $messageId, array $options = []): mixed
    {
        return $this->call($botToken, 'copyMessage', [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
            ...$options,
        ]);
    }


    /**
     * Редактирует текст сообщения
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $text     Новый текст
     * @param array  $options  chat_id+message_id или inline_message_id, parse_mode, reply_markup
     * @return mixed           Ответ Telegram API
     */
    public function editMessageText(string $botToken, string $text, array $options): mixed
    {
        return $this->call($botToken, 'editMessageText', [
            'text' => $text,
            ...$options, // chat_id + message_id ИЛИ inline_message_id, parse_mode, reply_markup
        ]);
    }


    /**
     * Редактирует подпись сообщения
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $caption  Новая подпись
     * @param array  $options  chat_id+message_id или inline_message_id, parse_mode, reply_markup
     * @return mixed           Ответ Telegram API
     */
    public function editMessageCaption(string $botToken, string $caption, array $options): mixed
    {
        return $this->call($botToken, 'editMessageCaption', [
            'caption' => $caption,
            ...$options, // chat_id + message_id ИЛИ inline_message_id, parse_mode, reply_markup
        ]);
    }


    /**
     * Редактирует медиа сообщения
     *
     * @param string $botToken Токен Telegram-бота
     * @param array  $media    Новый медиа-контент (InputMedia)
     * @param array  $options  chat_id+message_id или inline_message_id, reply_markup
     * @return mixed           Ответ Telegram API
     */
    public function editMessageMedia(string $botToken, array $media, array $options): mixed
    {
        return $this->call($botToken, 'editMessageMedia', [
            'media' => $media,
            ...$options, // chat_id + message_id ИЛИ inline_message_id, reply_markup
        ]);
    }


    /**
     * Редактирует inline-кнопки сообщения
     *
     * @param string $botToken     Токен Telegram-бота
     * @param array  $replyMarkup  Новый reply_markup
     * @param array  $options      chat_id+message_id или inline_message_id
     * @return mixed               Ответ Telegram API
     */
    public function editMessageReplyMarkup(string $botToken, array $replyMarkup, array $options): mixed
    {
        return $this->call($botToken, 'editMessageReplyMarkup', [
            'reply_markup' => $replyMarkup,
            ...$options, // chat_id + message_id ИЛИ inline_message_id
        ]);
    }


    /**
     * Удаляет сообщение
     *
     * @param string     $botToken  Токен Telegram-бота
     * @param string|int $chatId    ID чата
     * @param int        $messageId ID сообщения
     * @return mixed                Ответ Telegram API
     */
    public function deleteMessage(string $botToken, string|int $chatId, int $messageId): mixed
    {
        return $this->call($botToken, 'deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }


    /**
     * Ответ на callback_query
     *
     * @param string $botToken         Токен Telegram-бота
     * @param string $callbackQueryId  ID callback-запроса
     * @param array  $options          Доп. параметры (text, show_alert, url, cache_time)
     * @return mixed                   Ответ Telegram API
     */
    public function answerCallbackQuery(string $botToken, string $callbackQueryId, array $options = []): mixed
    {
        return $this->call($botToken, 'answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            ...$options,
        ]);
    }


    /**
     * Отправляет фото
     *
     * @param string     $botToken  Токен Telegram-бота
     * @param string|int $chatId    ID чата
     * @param string     $photo     Путь к файлу, file_id или URL
     * @param string|null $caption  Подпись
     * @param string     $parseMode parse_mode (по умолчанию HTML)
     * @param array      $options   Доп. параметры (reply_markup, caption_entities и пр.)
     * @return mixed                Ответ Telegram API
     */
    public function sendPhoto(
        string $botToken,
        string|int $chatId,
        string $photo,
        ?string $caption = null,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $files = [];
        $params = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
            ...$options,
        ];

        if (is_file($photo)) {
            $files['photo'] = $photo;
        } else {
            $params['photo'] = $photo; // file_id или URL
        }

        if ($caption !== null) {
            $params['caption'] = $caption;
        }

        return $this->call($botToken, 'sendPhoto', $params, $files);
    }


    /**
     * Отправляет видео
     *
     * @param string      $botToken  Токен Telegram-бота
     * @param string|int  $chatId    ID чата
     * @param string      $video     Путь к файлу, file_id или URL
     * @param string|null $caption   Подпись
     * @param string      $parseMode parse_mode (по умолчанию HTML)
     * @param array       $options   Доп. параметры (duration, width, height и пр.)
     * @return mixed                 Ответ Telegram API
     */
    public function sendVideo(
        string $botToken,
        string|int $chatId,
        string|CURLFile $video,
        ?string $caption = null,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $files = [];
        $params = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
            ...$options,
        ];

        // Варианты: CURLFile, локальный путь, либо file_id/URL
        if ($video instanceof CURLFile) {
            $files['video'] = $video; // обработается в call()
        } elseif (is_string($video) && is_file($video)) {
            $files['video'] = $video; // локальный файл
        } else {
            $params['video'] = $video; // URL или file_id
        }

        if ($caption !== null) {
            $params['caption'] = $caption;
        }

        return $this->call($botToken, 'sendVideo', $params, $files);
    }


    /**
     * Отправляет аудио
     *
     * @param string      $botToken  Токен Telegram-бота
     * @param string|int  $chatId    ID чата
     * @param string      $audio     Путь к файлу, file_id или URL
     * @param string|null $caption   Подпись
     * @param string      $parseMode parse_mode (по умолчанию HTML)
     * @param array       $options   Доп. параметры (performer, title, thumbnail и пр.)
     * @return mixed                 Ответ Telegram API
     */
    public function sendAudio(
        string $botToken,
        string|int $chatId,
        string $audio,
        ?string $caption = null,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $files = [];
        $params = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
            ...$options,
        ];

        if (is_file($audio)) {
            $files['audio'] = $audio;
        } else {
            $params['audio'] = $audio;
        }

        if ($caption !== null) {
            $params['caption'] = $caption;
        }

        return $this->call($botToken, 'sendAudio', $params, $files);
    }


    /**
     * Отправляет документ
     *
     * @param string      $botToken  Токен Telegram-бота
     * @param string|int  $chatId    ID чата
     * @param string      $document  Путь к файлу, file_id или URL
     * @param string|null $caption   Подпись
     * @param string      $parseMode parse_mode (по умолчанию HTML)
     * @param array       $options   Доп. параметры (thumbnail, disable_content_type_detection и пр.)
     * @return mixed                 Ответ Telegram API
     */
    public function sendDocument(
        string $botToken,
        string|int $chatId,
        string $document,
        ?string $caption = null,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $files = [];
        $params = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
            ...$options,
        ];

        if (is_file($document)) {
            $files['document'] = $document;
        } else {
            $params['document'] = $document;
        }

        if ($caption !== null) {
            $params['caption'] = $caption;
        }

        return $this->call($botToken, 'sendDocument', $params, $files);
    }


    /**
     * Отправляет голосовое сообщение
     *
     * @param string      $botToken  Токен Telegram-бота
     * @param string|int  $chatId    ID чата
     * @param string      $voice     Путь к файлу, file_id или URL
     * @param string|null $caption   Подпись
     * @param string      $parseMode parse_mode (по умолчанию HTML)
     * @param array       $options   Доп. параметры (duration и пр.)
     * @return mixed                 Ответ Telegram API
     */
    public function sendVoice(
        string $botToken,
        string|int $chatId,
        string $voice,
        ?string $caption = null,
        string $parseMode = 'HTML',
        array $options = [],
    ): mixed {
        $files = [];
        $params = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
            ...$options,
        ];

        if (is_file($voice)) {
            $files['voice'] = $voice;
        } else {
            $params['voice'] = $voice;
        }

        if ($caption !== null) {
            $params['caption'] = $caption;
        }

        return $this->call($botToken, 'sendVoice', $params, $files);
    }


    /**
     * Отправляет действие (typing, upload_photo, и т.д.)
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @param string     $action   Действие (typing, upload_photo, record_video, ...)
     * @return mixed               Ответ Telegram API
     */
    public function sendChatAction(string $botToken, string|int $chatId, string $action): mixed
    {
        return $this->call($botToken, 'sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }


    /**
     * Возвращает фотографии профиля пользователя
     *
     * @param string $botToken Токен Telegram-бота
     * @param int    $userId   ID пользователя
     * @param array  $options  Доп. параметры (offset, limit)
     * @return mixed           Ответ Telegram API
     */
    public function getUserProfilePhotos(string $botToken, int $userId, array $options = []): mixed
    {
        return $this->call($botToken, 'getUserProfilePhotos', [
            'user_id' => $userId,
            ...$options,
        ]);
    }


    /**
     * Возвращает информацию о файле
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $fileId   ID файла (file_id)
     * @return mixed           Ответ Telegram API
     */
    public function getFile(string $botToken, string $fileId): mixed
    {
        return $this->call($botToken, 'getFile', [
            'file_id' => $fileId,
        ]);
    }


    /**
     * Пин/анпин сообщений
     *
     * @param string     $botToken  Токен Telegram-бота
     * @param string|int $chatId    ID чата
     * @param int        $messageId ID сообщения
     * @param array      $options   Доп. параметры (disable_notification)
     * @return mixed                Ответ Telegram API
     */
    public function pinChatMessage(string $botToken, string|int $chatId, int $messageId, array $options = []): mixed
    {
        return $this->call($botToken, 'pinChatMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            ...$options,
        ]);
    }


    /**
     * Снимает пин с сообщения
     *
     * @param string     $botToken  Токен Telegram-бота
     * @param string|int $chatId    ID чата
     * @param int|null   $messageId ID сообщения (опционально)
     * @return mixed                Ответ Telegram API
     */
    public function unpinChatMessage(string $botToken, string|int $chatId, ?int $messageId = null): mixed
    {
        return $this->call($botToken, 'unpinChatMessage', array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ], static fn ($v) => $v !== null));
    }


    /**
     * Снимает пин со всех сообщений чата
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @return mixed               Ответ Telegram API
     */
    public function unpinAllChatMessages(string $botToken, string|int $chatId): mixed
    {
        return $this->call($botToken, 'unpinAllChatMessages', [
            'chat_id' => $chatId,
        ]);
    }


    /**
     * Информация о чате и его участниках
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @return mixed               Ответ Telegram API
     */
    public function getChat(string $botToken, string|int $chatId): mixed
    {
        return $this->call($botToken, 'getChat', [
            'chat_id' => $chatId,
        ]);
    }


    /**
     * Возвращает администраторов чата
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @return mixed               Ответ Telegram API
     */
    public function getChatAdministrators(string $botToken, string|int $chatId): mixed
    {
        return $this->call($botToken, 'getChatAdministrators', [
            'chat_id' => $chatId,
        ]);
    }


    /**
     * Возвращает информацию об участнике чата
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @param int        $userId   ID пользователя
     * @return mixed               Ответ Telegram API
     */
    public function getChatMember(string $botToken, string|int $chatId, int $userId): mixed
    {
        return $this->call($botToken, 'getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }


    /**
     * Возвращает количество участников чата
     *
     * @param string     $botToken Токен Telegram-бота
     * @param string|int $chatId   ID чата
     * @return mixed               Ответ Telegram API
     */
    public function getChatMemberCount(string $botToken, string|int $chatId): mixed
    {
        return $this->call($botToken, 'getChatMemberCount', [
            'chat_id' => $chatId,
        ]);
    }


    /**
     * Получает информацию о файле и загружает его
     *
     * @param string $botToken Токен Telegram-бота
     * @param string $fileId   Идентификатор файла
     * @param string $savePath Путь для сохранения файла (абсолютный путь)
     * @return string|null     Путь к сохранённому файлу
     */
    public function downloadFile(string $botToken, string $fileId, string $savePath): ?string
    {
        // Получаем информацию о файле через метод getFile
        $fileInfo = $this->call($botToken, 'getFile', [
            'file_id' => $fileId,
        ]);

        $filePath = $fileInfo['result']['file_path'] ?? null;

        if (!$filePath) {
            return null;
        }

        // Формируем URL для загрузки файла
        $fileUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

        // Создаём директорию, если она не существует
        is_dir($savePath) ?: mkdir($savePath, 0755, true);

        // Загружаем файл
        $fileContents = $this->getHttp()->get($fileUrl)->body();
        $saveFileName = rtrim($savePath, '/') . '/' . Hlp::fakeUuid7() . '_' . Hlp::fileName($filePath);

        return file_put_contents($saveFileName, $fileContents) ? $saveFileName : null;
    }
}
