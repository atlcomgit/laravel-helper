<?php

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LogLevel;
use Throwable;

/**
 * Сервис отправки сообщений в telegram
 */
final class TelegramService extends DefaultService
{
    /** Максимальное количество частей сообщения */
    public const TELEGRAM_MESSAGE_MAX_COUNT = 10;
    /** Максимальный размер одного сообщения */
    public const TELEGRAM_MESSAGE_MAX_LENGTH = 4000;
    /** Максимальный размер первого сообщения с вложением файла */
    public const TELEGRAM_CAPTION_MAX_LENGTH = 500;


    public function __construct(private TelegramApiService $telegramApiService) {}


    /**
     * Отправка в телеграм
     *
     * @param TelegramLogDto $dto
     * @return bool
     */
    public function sendMessage(TelegramLogDto $dto): bool
    {
        if (!lhConfig(ConfigEnum::TelegramLog, 'enabled')) {
            return false;
        }

        $telegramBotToken = lhConfig(ConfigEnum::TelegramLog, "{$dto->type}.token");
        $telegramChatId = lhConfig(ConfigEnum::TelegramLog, "{$dto->type}.chat_id");

        if (!$telegramBotToken || !$telegramChatId) {
            return false;
        }

        try {
            $dtoMessage = trim($dto->message ?? '', '"');
            $attachFile = null;

            if (mb_strlen($dtoMessage) <= 4096 && is_file($dtoMessage) && file_exists($dtoMessage)) {
                // Отправляем сообщение как файл
                $this->telegramApiService->sendMessageWithFile(
                    $telegramBotToken,
                    $telegramChatId,
                    basename($dtoMessage),
                    $this->prepareMessage($dto, "Отправка файла: {$dtoMessage}", 1, 1),
                );

            } else {
                // Добавление файла лога к первому сообщению
                $hasAttachFile = isDebugData()
                    && $dto->debugData
                    && in_array($dto->type, [LogLevel::ERROR, LogLevel::WARNING, LogLevel::DEBUG]);

                // Разбиваем большое сообщение на части
                $messages = Hlp::telegramBreakMessage(
                    $dtoMessage,
                    $hasAttachFile ? self::TELEGRAM_CAPTION_MAX_LENGTH : self::TELEGRAM_MESSAGE_MAX_LENGTH,
                    self::TELEGRAM_MESSAGE_MAX_LENGTH,
                    self::TELEGRAM_MESSAGE_MAX_COUNT,
                );
                $messageIndex = 0;
                $messageCount = count($messages);

                foreach ($messages as $message) {
                    $messageIndex++;

                    // Отправляем часть сообщения в телеграм
                    if ($messageIndex === 1 && $hasAttachFile) {
                        $this->telegramApiService->sendMessageWithFile(
                            $telegramBotToken,
                            $telegramChatId,
                            $attachFile = $this->attachDebugData($dto),
                            $this->prepareMessage($dto, $message, $messageIndex, $messageCount),
                        );

                        !($attachFile && is_file($attachFile)) ?: unlink($attachFile);
                        $attachFile = null;

                    } else {
                        $this->telegramApiService->sendMessage(
                            $telegramBotToken,
                            $telegramChatId,
                            $this->prepareMessage($dto, $message, $messageIndex, $messageCount),
                        );
                    }

                    if ($messageIndex > self::TELEGRAM_MESSAGE_MAX_COUNT) {
                        break;
                    }

                    if ($messageIndex < $messageCount) {
                        sleep(1);
                    }
                }
            }

            return true;

        } catch (Throwable $exception) {
            !($attachFile && is_file($attachFile)) ?: unlink($attachFile);

            return false;
        }
    }


    /**
     * Возвращает текст сообщения лога для telegram
     *
     * @param TelegramLogDto $dto
     * @param string $message
     * @param int $messageIndex
     * @param int $messageCount
     * @return string
     */
    public function prepareMessage(TelegramLogDto $dto, string $message, int $messageIndex, int $messageCount): string
    {
        $appName = config('app.name') ?? 'Unknown';
        $appEnv = config('app.env') ?? 'Unknown';
        $title = $dto->title ?? null;
        $method = $dto->method ?? null;
        $uri = $dto->uri ?? null;
        $uuid = $dto->uuid ?? null;
        $user = $dto->userId ? User::find($dto->userId) : null;
        $userId = $user?->id ?? $dto->userId ?? 'Unauthorized';
        $userName = $user?->full_name;
        $userEmail = $user?->email;
        $userPhone = $user?->phone;
        $branch = null; // app(GitService::class)->getBranchName();
        $ip = $dto->ip ?? null;
        $type = $dto->type ?? null;
        $time = Carbon::now()->format('d.m.Y в H:i:s');

        $showTitle = $messageIndex == 1;
        $showSpoiler = $messageIndex == $messageCount;

        $parts = "Сообщение разбито: на <b>{$messageCount} "
            . trans_choice('часть|части|частей', $messageCount)
            . "</b>";

        return str_replace(
            ['\\\\', "\n\n\n"],
            ['\\', "\n\n"],
            trim(
                ($showTitle
                    ? ""
                    . "<b>{$title}</b>\n"
                    . "{$method} <b>{$uri}</b>\n"
                    . ($messageCount > 1 ? "{$parts}\n" : '')
                    . "\n"
                    . ($uuid ? "uuid: <b>{$uuid}</b>\n" : '')
                    . "\n"
                    : ""
                )

                . "<pre language=\"json\">{$message}</pre>\n"

                . ($showSpoiler
                    ? "\n"
                    . "<tg-spoiler>"
                    . "Проект: <b>{$appName}</b>\n"
                    . "Окружение: <b>{$appEnv}</b>\n"
                    . ($branch ? "Ветка: <b>{$branch}</b>\n" : '')
                    . ($ip ? "Адрес: <b>{$ip}</b>\n" : '')
                    . ($userId ? "UserId: <b>{$userId}</b>\n" : '')
                    . ($userEmail ? "Email: <b>{$userEmail}</b>\n" : '')
                    . ($userPhone ? "Телефон: <b>{$userPhone}</b>\n" : '')
                    . ($userName ? "Пользователь: <b>{$userName}</b>\n" : '')
                    . "Время: <b>{$time}</b>\n"
                    . "Тип: <b>{$type}</b>\n"
                    . "</tg-spoiler>"
                    : ''
                )
            ),
        );
    }


    /**
     * Прикрепляем debug данные к сообщению
     *
     * @param TelegramLogDto $debugData
     * @return string
     */
    public function attachDebugData(TelegramLogDto $dto): string
    {
        $filePath = 'debug';
        $fileName = "debug_data_" . ($dto->uuid ?? uuid()) . ".json";
        // mkdir($filePath, 0777, true);
        Storage::disk('local')->makeDirectory($filePath);
        $filePathFull = Storage::disk('local')->path($filePath);

        try {
            chmod($filePathFull, 0777);
        } catch (Throwable $exception) {
        }

        Storage::disk('local')->put(
            "{$filePath}/{$fileName}",
            json_encode($dto->debugData, Hlp::jsonFlags() | JSON_PRETTY_PRINT),
        );

        return "{$filePathFull}/{$fileName}";
    }
}
