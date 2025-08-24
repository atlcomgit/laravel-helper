<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO копирования сообщения (copyMessage)
 */
class TelegramBotOutCopyMessageDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public string|int $fromChatId;
    public int $externalMessageId;
    public ?string $caption;
    public ?string $parseMode;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'caption' => null,
            'parseMode' => 'HTML',
        ];
    }
}
