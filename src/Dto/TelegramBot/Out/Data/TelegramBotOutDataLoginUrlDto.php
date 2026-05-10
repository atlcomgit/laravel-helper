<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto описания LoginUrl для inline кнопки Telegram.
 *
 * @method self url(string $url)
 * @method self forwardText(?string $forwardText)
 * @method self botUsername(?string $botUsername)
 * @method self requestWriteAccess(?bool $requestWriteAccess)
 */
class TelegramBotOutDataLoginUrlDto extends DefaultDto
{
    public string  $url;
    public ?string $forwardText        = null;
    public ?string $botUsername        = null;
    public ?bool   $requestWriteAccess = null;


    /**
     * Возвращает соответствия имен полей Telegram API.
     *
     * @return array
     */
    protected function mappings(): array
    {
        return [
            'forwardText'        => ['forwardText', 'forward_text'],
            'botUsername'        => ['botUsername', 'bot_username'],
            'requestWriteAccess' => ['requestWriteAccess', 'request_write_access'],
        ];
    }


    /**
     * Возвращает соответствия имен полей для сериализации в Telegram API.
     *
     * @return array
     */
    protected function serializationMappings(): array
    {
        return [
            'forwardText'        => 'forward_text',
            'botUsername'        => 'bot_username',
            'requestWriteAccess' => 'request_write_access',
        ];
    }


    /**
     * Преобразует dto к формату Telegram API.
     *
     * @param array $array
     * @return void
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull()->mappingKeys($this->serializationMappings());
    }
}
