<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotOptionTrait;

/**
 * Dto опций бота telegram
 * 
 * @method TelegramBotOutSendMessageDto disableWebPagePreview(bool $value)
 */
class TelegramBotOutMessageOptionsDto extends DefaultDto
{
    use TelegramBotOptionTrait;
}
