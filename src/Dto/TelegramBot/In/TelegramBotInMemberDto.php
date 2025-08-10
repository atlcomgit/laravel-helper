<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInMemberDto extends DefaultDto
{
    public ?TelegramBotInFromDto $user;
    public ?string $status;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'user' => TelegramBotInFromDto::class,
            'status' => 'string',
        ];
    }
}
