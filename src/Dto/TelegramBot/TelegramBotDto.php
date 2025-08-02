<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;

/**
 * Dto бота telegram
 */
abstract class TelegramBotDto extends DefaultDto
{
    /**
     * @inheritDoc
     */
    protected function onCreated(mixed $data): void
    {
        (bool)Lh::config(ConfigEnum::TelegramBot, 'enabled')
            ?: throw new LaravelHelperException('TelegramBot отключен');
    }
}
