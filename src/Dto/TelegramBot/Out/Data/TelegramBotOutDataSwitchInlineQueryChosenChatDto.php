<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto выбора чата для inline режима Telegram.
 *
 * @method self query(?string $query)
 * @method self allowUserChats(?bool $allowUserChats)
 * @method self allowBotChats(?bool $allowBotChats)
 * @method self allowGroupChats(?bool $allowGroupChats)
 * @method self allowChannelChats(?bool $allowChannelChats)
 */
class TelegramBotOutDataSwitchInlineQueryChosenChatDto extends DefaultDto
{
    public ?string $query             = null;
    public ?bool   $allowUserChats    = null;
    public ?bool   $allowBotChats     = null;
    public ?bool   $allowGroupChats   = null;
    public ?bool   $allowChannelChats = null;


    /**
     * Возвращает соответствия имен полей Telegram API.
     *
     * @return array
     */
    protected function mappings(): array
    {
        return [
            'allowUserChats'    => ['allowUserChats', 'allow_user_chats'],
            'allowBotChats'     => ['allowBotChats', 'allow_bot_chats'],
            'allowGroupChats'   => ['allowGroupChats', 'allow_group_chats'],
            'allowChannelChats' => ['allowChannelChats', 'allow_channel_chats'],
        ];
    }


    /**
     * Возвращает соответствия имен полей для сериализации в Telegram API.
     *
     * @return array
     */
    protected function serializationMappings(): array
    {
        return [
            'allowUserChats'    => 'allow_user_chats',
            'allowBotChats'     => 'allow_bot_chats',
            'allowGroupChats'   => 'allow_group_chats',
            'allowChannelChats' => 'allow_channel_chats',
        ];
    }


    /**
     * Преобразует dto к формату Telegram API.
     *
     * @param array $array
     * @return void
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull()->mappingKeys($this->serializationMappings());
    }
}
