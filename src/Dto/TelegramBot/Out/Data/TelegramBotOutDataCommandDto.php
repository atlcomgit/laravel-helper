<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotOutDataCommandDto extends DefaultDto
{
    public string $command;
    public string $description;
}
