<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInFromDto extends DefaultDto
{
    public int $id;
    public bool $isBot;
    public string $firstName;
    public string $userName;
    public string $languageCode;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'isBot' => 'boolean',
            'firstName' => 'string',
            'userName' => 'string',
            'languageCode' => 'string',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'isBot' => 'is_bot',
            'firstName' => 'first_name',
            'userName' => 'username',
            'languageCode' => 'language_code',
        ];
    }
}
