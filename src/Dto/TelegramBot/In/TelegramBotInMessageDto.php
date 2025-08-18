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
    public ?TelegramBotInContactDto $contact;
    public ?TelegramBotInMessageDto $replyToMessage;
    /** @var Collection<TelegramBotInEntitiesDto> */
    public ?Collection $entities;
    public string $text;
    public Carbon $date;
    public ?Carbon $editDate;
    public ?TelegramBotInReplyMarkupDto $replyMarkup;
    // public ?TelegramBotInWebAppDto $webAppData;
    public ?array $buttons;
    public ?array $keyboards;
    public ?array $video;
    public ?array $audio;
    public ?array $photo;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'messageId' => 'integer',
            'from' => TelegramBotInFromDto::class,
            'chat' => TelegramBotInChatDto::class,
            'contact' => TelegramBotInContactDto::class,
            'replyToMessage' => TelegramBotInMessageDto::class,
            'entities' => [TelegramBotInEntitiesDto::class],
            'text' => 'string',
            'date' => Carbon::class,
            'editDate' => Carbon::class,
            'replyMarkup' => TelegramBotInReplyMarkupDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'text' => '',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'messageId' => 'message_id',
            'text' => ['text', '--reply_to_message', 'caption'],
            'replyToMessage' => 'reply_to_message',
            'editDate' => 'edit_date',
            'replyMarkup' => 'reply_markup',
        ];
    }
}
