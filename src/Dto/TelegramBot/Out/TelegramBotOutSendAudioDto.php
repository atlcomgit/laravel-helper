<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO отправки аудио (sendAudio)
 */
class TelegramBotOutSendAudioDto extends TelegramBotOutDto
{
    public string|int $chatId;
    public string $audio; // file_id | url | path to local file
    public ?string $caption;
    public ?string $parseMode;
    public ?array $options;

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'caption' => null,
            'parseMode' => 'HTML',
            'options' => null,
        ];
    }
}
