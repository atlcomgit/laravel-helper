<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutMenuButtonDto;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Illuminate\Support\Collection;

trait TelegramBotKeyboardTrait
{
    /** @var Collection<array|TelegramBotOutMenuButtonDto> $keyboards */
    public ?Collection $keyboards;
    public ?bool $resizeKeyboard;
    public ?bool $oneTimeKeyboard;
    public ?bool $removeKeyboard;


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
        !($keyboards instanceof TelegramBotOutContactButtonDto) ?: $keyboards = [$keyboards];
        !isset($keyboards['text']) ?: $keyboards = [$keyboards];

        $keyboards = app(TelegramBotMessageService::class)->prepareKeyboards($keyboards);

        foreach ($keyboards as $keyboard) {
            $this->keyboards->push(is_array($keyboard) ? $keyboard : [$keyboard]);
        }

        return $this;
    }
}
