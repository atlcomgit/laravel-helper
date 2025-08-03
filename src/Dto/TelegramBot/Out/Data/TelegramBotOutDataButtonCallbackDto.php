<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotOutDataButtonCallbackDto extends DefaultDto
{
    public string $text;
    public string $callback;


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'callback' => ['callback', 'callbackData', 'callback_data'],
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        $this->mappingKeys(['callback' => 'callback_data']);
    }
}
