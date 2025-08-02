<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * Dto бота telegram
 */
class TelegramBotOutSendMessageDto extends TelegramBotOutDto
{
    public string $chatId;
    public string $text;
}
