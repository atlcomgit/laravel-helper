<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Webhook;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto бота telegram
 */
class TelegramBotWebhookResponseDto extends DefaultDto
{
    public bool $status;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'status' => true,
        ];
    }
}
