<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInChatDto extends DefaultDto
{
    public int $id;
    public string $firstName;
    public string $userName;
    public string $type;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'firstName' => 'string',
            'userName' => 'string',
            'type' => 'string',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'firstName' => ['first_name', 'title'],
            'userName' => ['username', 'title', '-first_name'],
        ];
    }
}
