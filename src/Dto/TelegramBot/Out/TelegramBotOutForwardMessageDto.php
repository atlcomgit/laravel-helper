<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO пересылки сообщения (forwardMessage)
 */
class TelegramBotOutForwardMessageDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public string|int $fromChatId;
    public int $messageId;
    public ?bool $disableNotification;

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'disableNotification' => null,
        ];
    }
}
