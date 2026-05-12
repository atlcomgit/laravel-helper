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
    public int                            $updateId;
    public TelegramBotInMessageDto        $message;
    public ?TelegramBotInCallbackQueryDto $callbackQuery;


    /**
     * Нормализует payload callback query до полного message DTO
     *
     * @param array $array
     * @return void
     */
    protected function onFilling(array &$array): void
    {
        $callbackQueryFrom = $array['callback_query']['from']
            ?? $array['callbackQuery']['from']
            ?? null;

        if (!$callbackQueryFrom) {
            return;
        }

        if (is_array($array['callback_query']['message'] ?? null)) {
            $array['callback_query']['message']['from'] ??= $callbackQueryFrom;
        }

        if (is_array($array['callbackQuery']['message'] ?? null)) {
            $array['callbackQuery']['message']['from'] ??= $callbackQueryFrom;
        }
    }


    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return [
            'updateId'      => ['required', 'integer'],
            'message'       => ['required', 'array'],
            'callbackQuery' => ['nullable', 'array'],
        ];
    }


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'updateId'      => 'integer',
            'message'       => TelegramBotInMessageDto::class,
            'callbackQuery' => TelegramBotInCallbackQueryDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'updateId'      => 'update_id',
            'message'       => ['message', 'edited_message', 'callback_query.message'],
            'callbackQuery' => ['callbackQuery', 'callback_query'],
        ];
    }
}
