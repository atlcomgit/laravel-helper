<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO редактирования подписи сообщения (editMessageCaption)
 * 
 * @method self externalChatId(string|int $externalChatId)
 * @method self messageId(?int $messageId)
 * @method self inlineMessageId(?string $inlineMessageId)
 * @method self caption(?string $caption)
 * @method self parseMode(string $parseMode)
 * @method self replyMarkup(?array $replyMarkup)
 */
class TelegramBotOutEditMessageCaptionDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public ?int       $messageId;
    public ?string    $inlineMessageId;
    public ?string    $caption;
    public string     $parseMode;
    public ?array     $replyMarkup;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'chatId'          => null,
            'messageId'       => null,
            'inlineMessageId' => null,
            'caption'         => null,
            'parseMode'       => 'HTML',
            'replyMarkup'     => null,
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
}
