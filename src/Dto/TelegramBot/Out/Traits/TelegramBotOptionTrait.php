<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutMessageOptionsDto;

/**
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultDto
 */
trait TelegramBotOptionTrait
{
    public ?bool $disableWebPagePreview;


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
