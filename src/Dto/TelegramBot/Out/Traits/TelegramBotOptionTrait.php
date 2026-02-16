<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutMessageOptionsDto;

/**
 * Опции сообщений бота телеграм
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultDto
 */
trait TelegramBotOptionTrait
{
    // Отключает предпросмотр ссылок в сообщении
    public ?bool $disableWebPagePreview;
    // Всегда показывать сообщение, даже если оно является дубликатом последнего отправленного сообщения
    public ?bool $alwaysShowMessage;


    /**
     * Устанавливает значения опций
     *
     * @param TelegramBotOutMessageOptionsDto|null $options
     * @return static
     */
    public function setMessageOptions(?TelegramBotOutMessageOptionsDto $options): static
    {
        $this->merge($options);

        return $this;
    }
}
