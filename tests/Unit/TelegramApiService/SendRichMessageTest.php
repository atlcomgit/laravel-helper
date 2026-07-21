<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\TelegramApiService;

use Atlcom\LaravelHelper\Services\TelegramApiService;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты отправки Telegram Rich Messages.
 */
final class SendRichMessageTest extends PackageTestCase
{
    /**
     * Проверяет JSON payload без реального HTTP-запроса.
     * @see \Atlcom\LaravelHelper\Services\TelegramApiService::sendRichMessage()
     */
    #[Test]
    public function sendRichMessageSendsJsonPayloadAndFiltersEmptyThreadId(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1001]], 200),
        ]);

        // Проверяем обязательные поля, дополнительные параметры и защиту от их переопределения.
        $botToken = '123456:test-token';
        $chatId = -1001234567890;
        $richMessage = [
            'html' => '<h2>Тестовое rich-сообщение</h2>',
        ];
        $options = [
            'disable_notification' => true,
            'chat_id' => 'ignored-chat-id',
            'rich_message' => ['html' => '<p>Игнорируется</p>'],
        ];

        app(TelegramApiService::class)->sendRichMessage($botToken, $chatId, $richMessage, $options);
        app(TelegramApiService::class)->sendRichMessage($botToken, $chatId, $richMessage, $options, 42);

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) use ($botToken, $chatId, $richMessage): bool {
            $contentType = (string) ($request->header('Content-Type')[0] ?? '');
            $data = $request->data();

            return $request->method() === 'POST'
                && $request->url() === "https://api.telegram.org/bot{$botToken}/sendRichMessage"
                && str_contains($contentType, 'application/json')
                && count($data) === 3
                && $data['chat_id'] === $chatId
                && $data['rich_message'] === $richMessage
                && $data['disable_notification'] === true;
        });
        Http::assertSent(function (Request $request) use ($botToken, $chatId, $richMessage): bool {
            $contentType = (string) ($request->header('Content-Type')[0] ?? '');
            $data = $request->data();

            return $request->method() === 'POST'
                && $request->url() === "https://api.telegram.org/bot{$botToken}/sendRichMessage"
                && str_contains($contentType, 'application/json')
                && count($data) === 4
                && $data['chat_id'] === $chatId
                && $data['rich_message'] === $richMessage
                && $data['disable_notification'] === true
                && $data['message_thread_id'] === 42;
        });
    }
}
