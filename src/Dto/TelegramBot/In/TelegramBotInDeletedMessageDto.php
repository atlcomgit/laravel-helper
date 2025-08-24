<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInDeletedMessageDto extends DefaultDto
{
    public int $externalMessageId;
    public bool $status;
    public ?string $statusMessage;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'externalMessageId' => 'integer',
            'status' => 'boolean',
            'statusMessage' => 'string',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'externalMessageId' => 'external_message_id',
            'statusMessage' => 'status_message',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'status' => false,
        ];
    }
}
