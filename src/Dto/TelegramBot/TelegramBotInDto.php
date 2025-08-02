<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;

/**
 * Dto бота telegram
 */
class TelegramBotInDto extends TelegramBotDto
{
    public int $updateId;
    public TelegramBotInMessageDto $message;


    /**
     * @inheritDoc
     */
    public function rules(): array {
        return [
            'updateId' => ['required', 'integer'],
            'message' => ['required', 'array'],
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
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'updateId' => 'update_id',
            'message' => ['message', 'edited_message'],
        ];
    }
}
