<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Webhook;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInCallbackQueryDto;

/**
 * @internal
 * Dto бота telegram
 */
class TelegramBotWebhookResponseDto extends DefaultDto
{
    public bool $status;
    public ?TelegramBotInCallbackQueryDto $callbackQuery;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'status' => true,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull()
            ->excludeKeys(['callbackQuery'])
            ->includeArray([
                ...(
                    $this->callbackQuery
                    ? [
                        'method' => 'answerCallbackQuery',
                        'callback_query_id' => $this->callbackQuery->id,
                        'show_alert' => false,
                    ]
                    : []),
            ]);
    }
}
