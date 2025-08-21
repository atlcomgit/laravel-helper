<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInCallbackQueryDto extends DefaultDto
{
    public string $id;
    public TelegramBotInFromDto $from;
    public string $data;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'from' => TelegramBotInFromDto::class,
            'data' => 'string',
        ];
    }
}
