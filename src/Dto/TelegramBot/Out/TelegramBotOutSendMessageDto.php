<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonUrlDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Illuminate\Support\Collection;

/**
 * Dto бота telegram
 */
class TelegramBotOutSendMessageDto extends TelegramBotOutDto
{
    public string $chatId;
    public string $text;
    public ?string $slug;

    /** @var Collection<array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto> $buttons */
    public ?Collection $buttons;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'text' => '',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'chatId' => ['chatId', 'chat_id', 'telegramBotChat.external_chat_id', 'telegram_bot_chat.external_chat_id'],
        ];
    }


    /**
     * Добавляет слаг к сообщению
     *
     * @param string $slug
     * @return static
     */
    public function addSlug(string $slug): static
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
    public function addText(string $text): static
    {
        $this->text = Hlp::stringConcat(PHP_EOL, $this->text, $text);

        return $this;
    }


    /**
     * Добавляет текст к сообщению
     *
     * @param string $text
     * @return static
     */
    public function replaceText(string $text): static
    {
        $this->text = $text;

        return $this;
    }


    /**
     * Добавляет кнопку к сообщению
     *
     * @param TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons
     * @return static
     */
    public function addButton(TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons): static
    {
        $this->addButtons([$buttons]);

        return $this;
    }


    /**
     * Добавляет несколько кнопок к сообщению
     *
     * @param array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons
     * @param mixed 
     * @return static
     */
    public function addButtons(
        array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons,
    ): static {
        $this->buttons ??= collect([]);
        !($buttons instanceof TelegramBotOutDataButtonCallbackDto) ?: $buttons = [$buttons];
        !($buttons instanceof TelegramBotOutDataButtonUrlDto) ?: $buttons = [$buttons];
        !isset($button['text']) ?: $buttons = [$buttons];

        $this->buttons->push($this->prepareButtons($buttons));

        return $this;
    }


    /**
     * Добавляет несколько кнопок к сообщению
     *
     * @param array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons
     * @param mixed 
     * @return static
     */
    public function replaceButtons(
        array|TelegramBotOutDataButtonCallbackDto|TelegramBotOutDataButtonUrlDto $buttons,
    ): static {
        $this->buttons = collect([]);
        $this->addButtons($buttons);

        return $this;
    }


    /**
     * Создает массив кнопок из dto
     *
     * @param array $buttons
     * @return array
     */
    protected function prepareButtons(array $buttons): array
    {
        $buttons = array_map(
            fn ($button) => match (true) {
                $button instanceof TelegramBotOutDataButtonCallbackDto => $button,
                $button instanceof TelegramBotOutDataButtonUrlDto => $button,
                is_array($button) && is_array(Hlp::arrayFirst($button)) => $this->prepareButtons($button),
                is_array($button) && isset($button['text']) && isset($button['callback'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['callbackData'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['callback_data'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['url'])
                => TelegramBotOutDataButtonUrlDto::create($button),

                default => null,
            },
            $buttons,
        );

        return array_filter($buttons);
    }
}
