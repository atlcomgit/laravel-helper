<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO удаления сообщения (deleteMessage)
 * 
 * @method self externalChatId(string|int $externalChatId)
 * @method self externalMessageId(int $externalMessageId)
 */
class TelegramBotOutDeleteMessageDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public int $externalMessageId;
}
