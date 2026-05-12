<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\TelegramBotInDto;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тест создания входящего DTO Telegram из callback query
 */
final class CreateFromCallbackQueryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laravel-helper.telegram_bot.enabled', true);
    }


    /**
     * Тест создания DTO из callback query без отправителя в message
     * @see \Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto::create()
     *
     * @return void
     */
    #[Test]
    public function createBuildsCallbackPayloadWhenMessageHasNoFrom(): void
    {
        $dto = TelegramBotInDto::create([
            'update_id'      => 412853128,
            'callback_query' => [
                'id'            => '6884540731851447403',
                'from'          => [
                    'id'            => 1602932049,
                    'is_bot'        => false,
                    'first_name'    => 'Ром',
                    'username'      => 'Roma89896',
                    'language_code' => 'ru',
                ],
                'message'       => [
                    'message_id' => 237728,
                    'chat'       => [
                        'id'         => 1602932049,
                        'first_name' => 'Ром',
                        'username'   => 'Roma89896',
                        'type'       => 'private',
                    ],
                    'date'       => 0,
                ],
                'chat_instance' => '6543842346430987516',
                'data'          => 'Сегодня',
            ],
        ]);

        $this->assertSame(412853128, $dto->updateId);
        $this->assertSame(1602932049, $dto->message->from->id);
        $this->assertNotNull($dto->callbackQuery);
        $this->assertSame(1602932049, $dto->callbackQuery->from->id);
        $this->assertSame(237728, $dto->message->messageId);
        $this->assertSame(1602932049, $dto->message->chat->id);
        $this->assertSame('Сегодня', $dto->callbackQuery->data);
    }
}
