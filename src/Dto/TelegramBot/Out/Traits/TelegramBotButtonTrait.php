<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonUrlDto;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Illuminate\Support\Collection;

trait TelegramBotButtonTrait
{
    /** @var Collection<array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto> $buttons */
    public ?Collection $buttons;


    /**
     * Добавляет несколько inline кнопок к сообщению
     *
     * @param array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons
     * @return static
     */
    public function setButtons(
        array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons,
    ): static {
        $this->buttons = collect([]);
        $this->addButtons($buttons);

        return $this;
    }


    /**
     * Добавляет inline кнопку к сообщению
     *
     * @param TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $button
     * @return static
     */
    public function addButton(TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $button): static
    {
        $this->addButtons([$button]);

        return $this;
    }


    /**
     * Добавляет несколько inline кнопок к сообщению
     *
     * @param array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons
     * @return static
     */
    public function addButtons(
        array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons,
    ): static {
        $this->buttons ??= collect([]);
        !($buttons instanceof TelegramBotOutDataButtonCallbackDto) ?: $buttons = [$buttons];
        !($buttons instanceof TelegramBotOutDataButtonUrlDto) ?: $buttons = [$buttons];
        !isset($buttons['text']) ?: $buttons = [$buttons];

        $buttons = app(TelegramBotMessageService::class)->prepareButtons($buttons);

        foreach ($buttons as $button) {
            $this->buttons->push(is_array($button) ? $button : [$button]);
        }

        return $this;
    }
}
