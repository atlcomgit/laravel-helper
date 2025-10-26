<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotKeyboardTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotOptionTrait;

/**
 * Dto опций бота telegram
 * 
 * @method TelegramBotOutSendMessageDto resizeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto oneTimeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto removeKeyboard(bool $value)
 * @method TelegramBotOutSendMessageDto disableWebPagePreview(bool $value)
 */
class TelegramBotOutMessageOptionsDto extends DefaultDto
{
    use TelegramBotKeyboardTrait;
    use TelegramBotOptionTrait;
}
