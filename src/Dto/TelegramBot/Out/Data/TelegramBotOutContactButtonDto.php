<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * @method self text(string $text)
 * @method self requestContact(?bool $requestContact)
 */
class TelegramBotOutContactButtonDto extends DefaultDto
{
    public string $text;
    public ?bool $requestContact = true; // Отправка запроса контактов


    /**
     * @inheritDoc
     */
    protected function mappings(): array {
        return [
            'requestContact' => 'request_contact',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull()->mappingKeys($this->mappings());
    }
}
