<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto кнопки Telegram для копирования текста.
 *
 * @method self text(string $text)
 */
class TelegramBotOutDataCopyTextButtonDto extends DefaultDto
{
    public string $text;
}
