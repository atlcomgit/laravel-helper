<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;

/**
 * Dto бота telegram
 */
class TelegramBotOutSetWebhookDto extends TelegramBotOutDto
{
    public string $url;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'url' => Lh::config(ConfigEnum::TelegramBot, 'webhook'),
        ];
    }


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'url' => static fn ($v) => rtrim($v, '/'),
        ];
    }
}
