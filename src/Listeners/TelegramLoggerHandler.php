<?php

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\TelegramLogDto;
use Atlcom\LaravelHelper\Jobs\TelegramLogJob;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
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
class TelegramLoggerHandler extends AbstractProcessingHandler
{
    public function write(LogRecord $record): void
    {
        if ($record->message && config('laravel-helper.telegram_log.enabled', false)) {

            $type = $title = $chatId = '';

            $message = json_decode($record->message, true) ?? [];
            !is_string($message) ?: $message = json_decode($message, true) ?? [];
            $message = $message
                ? json_encode($message, Helper::jsonFlags() | JSON_PRETTY_PRINT)
                : $record->message;

            $maxSize = TelegramService::TELEGRAM_MESSAGE_MAX_LENGTH * TelegramService::TELEGRAM_MESSAGE_MAX_LENGTH;
            if (Str::length($message) > (config('telegraph.message.max_size') ?: $maxSize)) {
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
                !config("laravel-helper.telegram_log.{$type}.enabled", false)
                || !($chatId = config("laravel-helper.telegram_log.{$type}.chat_id"))
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
                    DateInterval::createFromDateString(config("laravel-helper.telegram_log.{$type}.cache_ttl")),
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

            if (
                app(LaravelHelperService::class)
                    ->checkExclude("laravel-helper.telegram_log.{$type}.exclude", $dto->toArray())
            ) {
                return;
            }

            !$type ?: queue(TelegramLogJob::class, $dto, config('laravel-helper.telegram_log.queue'));
        }
    }
}
