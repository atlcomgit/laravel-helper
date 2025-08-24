<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO удаления сообщений (deleteMessages)
 * 
 * @method TelegramBotOutDeleteMessagesDto externalMessageIds(array $value)
 */
class TelegramBotOutDeleteMessagesDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public array $externalMessageIds;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'externalMessageIds' => [],
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
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'externalMessageIds' => static fn ($v) => array_map(static fn ($id) => Hlp::castToInt($id), $v),
        ];
    }
}
