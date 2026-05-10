<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\TelegramBotOutDataButtonCallbackDto;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Enums\TelegramBotButtonStyleEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тест сериализации inline-кнопки Telegram
 */
final class ToArrayTest extends TestCase
{
    /**
     * Тест метода DTO
     * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto::toArray()
     *
     * @return void
     */
    #[Test]
    public function toArraySerializesTelegramInlineButtonFields(): void
    {
        $dto = TelegramBotOutDataButtonCallbackDto::create([
            'text'                        => 'Test',
            'callback'                    => 'cb',
            'style'                       => TelegramBotButtonStyleEnum::Success,
            'iconCustomEmojiId'           => 'emoji-id',
            'switchInlineQueryChosenChat' => [
                'allowUserChats' => true,
            ],
            'copyText'                    => [
                'text' => 'copy',
            ],
        ]);

        $this->assertSame([
            'text'                            => 'Test',
            'callback_data'                   => 'cb',
            'style'                           => 'success',
            'icon_custom_emoji_id'            => 'emoji-id',
            'switch_inline_query_chosen_chat' => [
                'allow_user_chats' => true,
            ],
            'copy_text'                       => [
                'text' => 'copy',
            ],
        ], $dto->toArray());
    }
}
