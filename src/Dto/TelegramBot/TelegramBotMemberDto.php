<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot;

use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMyChatMemberDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;

/**
 * Dto бота telegram
 */
class TelegramBotMemberDto extends TelegramBotDto
{
    public int $updateId;
    public TelegramBotInMyChatMemberDto $myChatMember;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'updateId' => 'integer',
            'myChatMember' => TelegramBotInMyChatMemberDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'updateId' => 'update_id',
            'myChatMember' => 'my_chat_member',
        ];
    }
}
