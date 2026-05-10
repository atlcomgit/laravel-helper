<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO пересылки сообщения (forwardMessage)
 * 
 * @method self externalChatId(string|int $externalChatId)
 * @method self fromChatId(string|int $fromChatId)
 * @method self externalMessageId(int $externalMessageId)
 * @method self disableNotification(?bool $disableNotification)
 */
class TelegramBotOutForwardMessageDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public string|int $fromChatId;
    public int $externalMessageId;
    public ?bool $disableNotification;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'disableNotification' => null,
        ];
    }
}
