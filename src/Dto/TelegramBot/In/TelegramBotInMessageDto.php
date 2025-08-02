<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TelegramBotInMessageDto extends DefaultDto
{
    public int $messageId;
    public TelegramBotInFromDto $from;
    public TelegramBotInChatDto $chat;
    public ?TelegramBotInMessageDto $replyToMessage;
    /** @var Collection<TelegramBotInEntitiesDto> */
    public ?Collection $entities;
    public string $text;
    public Carbon $date;
    public ?Carbon $editDate;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'messageId' => 'integer',
            'from' => TelegramBotInFromDto::class,
            'chat' => TelegramBotInChatDto::class,
            'replyToMessage' => TelegramBotInMessageDto::class,
            'entities' => [TelegramBotInEntitiesDto::class],
            'text' => 'string',
            'date' => Carbon::class,
            'editDate' => Carbon::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'messageId' => 'message_id',
            'replyToMessage' => 'reply_to_message',
            'editDate' => 'edit_date',
        ];
    }
}
