<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot;

use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInCallbackQueryDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;

/**
 * Dto бота telegram
 */
class TelegramBotInDto extends TelegramBotDto
{
    public int $updateId;
    public TelegramBotInMessageDto $message;
    public ?TelegramBotInCallbackQueryDto $callbackQuery;


    /**
     * @inheritDoc
     */
    public function rules(): array {
        return [
            'updateId' => ['required', 'integer'],
            'message' => ['required', 'array'],
            'callbackQuery' => ['nullable', 'array'],
        ];
    }


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'updateId' => 'integer',
            'message' => TelegramBotInMessageDto::class,
            'callbackQuery' => TelegramBotInCallbackQueryDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'updateId' => 'update_id',
            'message' => ['message', 'edited_message', 'callback_query.message'],
            'callbackQuery' => ['callbackQuery', 'callback_query'],
        ];
    }
}
