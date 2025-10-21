<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Exceptions;

use Atlcom\LaravelHelper\Defaults\DefaultException;

/**
 * Исключение без отправки сообщения в телеграм
 */
class WithoutTelegramException extends DefaultException
{
    protected bool $withoutTelegram = true;


    /**
     * Возвращает состояние флага отключения отправки лога в телеграм
     *
     * @return bool
     */
    public function isWithoutTelegram(): bool
    {
        return $this->withoutTelegram;
    }


    /**
     * Устанавливает состояние флага отключения отправки лога в телеграм
     *
     * @param bool $withoutTelegram
     * @return static
     */
    public function setWithoutTelegram(bool $withoutTelegram): static
    {
        $this->withoutTelegram = $withoutTelegram;

        return $this;
    }
}
