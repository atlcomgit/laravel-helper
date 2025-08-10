<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInMyChatMemberDto extends DefaultDto
{
    public TelegramBotInFromDto $from;
    public TelegramBotInChatDto $chat;
    public TelegramBotInMemberDto $oldChatMember;
    public TelegramBotInMemberDto $newChatMember;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'from' => TelegramBotInFromDto::class,
            'chat' => TelegramBotInChatDto::class,
            'oldChatMember' => TelegramBotInMemberDto::class,
            'newChatMember' => TelegramBotInMemberDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'oldChatMember' => 'old_chat_member',
            'newChatMember' => 'new_chat_member',
        ];
    }
}
