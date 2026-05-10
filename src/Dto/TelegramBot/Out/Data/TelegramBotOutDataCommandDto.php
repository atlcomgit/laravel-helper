<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * @method self command(string $command)
 * @method self description(string $description)
 */
class TelegramBotOutDataCommandDto extends DefaultDto
{
    public string $command;
    public string $description;
}
