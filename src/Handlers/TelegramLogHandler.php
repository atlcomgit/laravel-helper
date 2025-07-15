<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Handlers;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramService;
use DateInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

/**
 * Обработчик логирования сообщений в телеграм
 */
class TelegramLogHandler extends AbstractProcessingHandler
{
    public function write(LogRecord $record): void
    {
        if ($record->message && Lh::config(ConfigEnum::TelegramLog, 'enabled', false)) {

            $type = $title = $chatId = '';

            $message = json_decode($record->message, true) ?? [];
            !is_string($message) ?: $message = json_decode($message, true) ?? [];
            $message = $message
                ? json_encode($message, Hlp::jsonFlags() | JSON_PRETTY_PRINT)
                : $record->message;

            $maxSize = TelegramService::TELEGRAM_MESSAGE_MAX_COUNT * TelegramService::TELEGRAM_MESSAGE_MAX_LENGTH;
            if (Str::length($message) > $maxSize) {
                return;
            }

            switch ($record->level) {
                case Level::Info:
                case LogLevel::INFO:
                    $title = "ИНФОРМАЦИЯ: {$record->channel}";
                    $type = LogLevel::INFO;
                    break;

                case Level::Error:
                case LogLevel::ERROR:
                    $title = "ОШИБКА: {$record->channel}";
                    $type = LogLevel::ERROR;
                    break;

                case Level::Warning:
                case LogLevel::WARNING:
                    $title = "ПРЕДУПРЕЖДЕНИЕ: {$record->channel}";
                    $type = LogLevel::WARNING;
                    break;

                case Level::Debug:
                case LogLevel::DEBUG:
                    $title = "ОТЛАДКА: {$record->channel}";
                    $type = LogLevel::DEBUG;
                    break;

                default:
                    if (($level = $record->context['level'] ?? null) instanceof TelegramTypeEnum) {
                        $title = Str::upper($level->label());
                        $type = $level->value;
                    }
            }

            if (
                !Lh::config(ConfigEnum::TelegramLog, "{$type}.enabled", false)
                || !($chatId = Lh::config(ConfigEnum::TelegramLog, "{$type}.chat_id"))
            ) {
                return;
            }

            $file = $record->context['file'] ?? '';
            $line = $record->context['line'] ?? '';
            $cacheHash = "telegram:file:{$type}:" . md5("{$file}{$line}");

            if ($file) {
                if (Cache::get($cacheHash)) {
                    return;
                }

                Cache::set(
                    $cacheHash,
                    $file,
                    DateInterval::createFromDateString(Lh::config(ConfigEnum::TelegramLog, "{$type}.cache_ttl")),
                );
            }

            $dto = TelegramLogDto::create(
                chatId: $chatId,
                title: $title,
                message: $message,
                type: $type,
                uuid: $record->context['uuid'] ?? null,
                debugData: $record->context['debugData'] ?? null,
            );

            $dto->dispatch();
        }
    }
}
