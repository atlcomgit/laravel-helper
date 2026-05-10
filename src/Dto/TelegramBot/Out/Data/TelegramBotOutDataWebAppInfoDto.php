<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto описания Web App для inline кнопки Telegram.
 *
 * @method self url(string $url)
 */
class TelegramBotOutDataWebAppInfoDto extends DefaultDto
{
    public string $url;
}
