<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotButtonTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotKeyboardTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotOptionTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * Dto бота telegram
 * 
 * @method TelegramBotOutSendMessageDto resizeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto oneTimeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto removeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto disableWebPagePreview(bool $value)
 */
class TelegramBotOutSendMessageDto extends TelegramBotOutDto
{
    use TelegramBotButtonTrait;
    use TelegramBotKeyboardTrait;
    use TelegramBotOptionTrait;


    public string $externalChatId;
    public ?int $messageThreadId;
    public string $text;
    public ?string $slug;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'text' => '',
            'resizeKeyboard' => true,
            'oneTimeKeyboard' => false,
            'removeKeyboard' => false,
            'disableWebPagePreview' => false,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'externalChatId' => [
                'chatId',
                'chat_id',
                'telegramBotChat.external_chat_id',
                'telegram_bot_chat.external_chat_id',
                'external_chat_id',
            ],
        ];
    }


    /**
     * Добавляет слаг к сообщению
     *
     * @param string|null $slug
     * @return static
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }


    /**
     * Добавляет текст к сообщению
     *
     * @param string $text
     * @return static
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }


    /**
     * Добавляет текст к сообщению
     *
     * @param string $text
     * @return static
     */
    public function addText(string $text): static
    {
        $this->text .= PHP_EOL . $text;

        return $this;
    }
}
