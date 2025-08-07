<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonUrlDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutMenuButtonDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Illuminate\Support\Collection;

/**
 * Dto бота telegram
 * 
 * @method resizeKeyboard()
 * @method oneTimeKeyboard()
 * @method removeKeyboard()
 */
class TelegramBotOutSendMessageDto extends TelegramBotOutDto
{
    public string $externalChatId;
    public string $text;
    public ?string $slug;

    /** @var Collection<array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto> $buttons */
    public ?Collection $buttons;

    /** @var Collection<array|TelegramBotOutMenuButtonDto> $keyboards */
    public ?Collection $keyboards;
    public ?bool $resizeKeyboard;
    public ?bool $oneTimeKeyboard;
    public ?bool $removeKeyboard;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'text' => '',
            'resizeKeyboard' => true,
            'oneTimeKeyboard' => false,
            'removeKeyboard' => false,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'externalChatId' => [
                'chatId',
                'chat_id',
                'telegramBotChat.external_chat_id',
                'telegram_bot_chat.external_chat_id',
                'external_chat_id',
            ],
        ];
    }


    /**
     * Добавляет слаг к сообщению
     *
     * @param string|null $slug
     * @return static
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }


    /**
     * Добавляет текст к сообщению
     *
     * @param string $text
     * @return static
     */
    public function setText(string $text): static
    {
        $this->text = trim($text);

        return $this;
    }


    /**
     * Добавляет текст к сообщению
     *
     * @param string $text
     * @return static
     */
    public function addText(string $text): static
    {
        $this->text = Hlp::stringConcat(PHP_EOL, ltrim($this->text), $text);

        return $this;
    }


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


    /**
     * Добавляет несколько keyboard кнопок к сообщению
     *
     * @param array|TelegramBotOutMenuButtonDto $keyboards
     * @return static
     */
    public function setKeyboards(array|TelegramBotOutMenuButtonDto $keyboards): static
    {
        $this->keyboards = collect([]);
        $this->addKeyboards($keyboards);

        return $this;
    }


    /**
     * Добавляет keyboard кнопку к сообщению
     *
     * @param TelegramBotOutMenuButtonDto $keyboard
     * @return static
     */
    public function addKeyboard(TelegramBotOutMenuButtonDto $keyboard): static
    {
        $this->addKeyboards([$keyboard]);

        return $this;
    }


    /**
     * Добавляет несколько keyboard кнопок к сообщению
     *
     * @param array|TelegramBotOutMenuButtonDto $keyboards
     * @return static
     */
    public function addKeyboards(array|TelegramBotOutMenuButtonDto $keyboards): static
    {
        $this->keyboards ??= collect([]);
        !($keyboards instanceof TelegramBotOutMenuButtonDto) ?: $keyboards = [$keyboards];
        !isset($keyboards['text']) ?: $keyboards = [$keyboards];

        $keyboards = app(TelegramBotMessageService::class)->prepareKeyboards($keyboards);

        foreach ($keyboards as $keyboard) {
            $this->keyboards->push(is_array($keyboard) ? $keyboard : [$keyboard]);
        }

        return $this;
    }
}
