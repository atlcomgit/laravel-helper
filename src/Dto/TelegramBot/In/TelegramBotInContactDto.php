<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInContactDto extends DefaultDto
{
    public int $userId;
    public string $firstName;
    public string $phoneNumber;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'userId' => 'integer',
            'firstName' => 'string',
            'phoneNumber' => 'string',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'userId' => 'user_id',
            'firstName' => 'first_name',
            'phoneNumber' => 'phone_number',
        ];
    }
}
