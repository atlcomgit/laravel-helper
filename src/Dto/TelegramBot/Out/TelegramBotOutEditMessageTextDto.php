<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO редактирования текста (editMessageText)
 */
class TelegramBotOutEditMessageTextDto extends TelegramBotOutDto
{
    public ?string $chatId;
    public ?int $messageId;
    public ?string $inlineMessageId;
    public string $text;
    public ?string $parseMode;
    public ?array $replyMarkup;

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'chatId' => null,
            'messageId' => null,
            'inlineMessageId' => null,
            'parseMode' => 'HTML',
            'replyMarkup' => null,
        ];
    }
}
