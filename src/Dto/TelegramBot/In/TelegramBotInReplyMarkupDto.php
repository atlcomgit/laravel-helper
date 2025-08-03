<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInReplyMarkupDto extends DefaultDto
{
    public ?array $buttons;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'buttons' => 'array',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'buttons' => 'inline_keyboard',
        ];
    }
}
