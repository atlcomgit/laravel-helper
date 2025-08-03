<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Illuminate\Support\Collection;

class TelegramBotOutDataButtonUrlDto extends DefaultDto
{
    public string $text;
    public string $url;
}