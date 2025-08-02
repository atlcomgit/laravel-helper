<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInEntitiesDto extends DefaultDto
{
    public ?int $offset;
    public ?int $length;
    public ?string $type;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'offset' => 'integer',
            'length' => 'integer',
            'type' => 'string',
        ];
    }
}
