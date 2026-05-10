<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotButtonTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotKeyboardTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO отправки аудио (sendAudio)
 * 
 * @method self externalChatId(string|int $externalChatId)
 * @method self audio(string $audio)
 * @method self caption(?string $caption)
 * @method self slug(?string $slug)
 * @method self options(?array $options)
 */
class TelegramBotOutSendAudioDto extends TelegramBotOutDto
{
    use TelegramBotButtonTrait;
    use TelegramBotKeyboardTrait;


    public string|int $externalChatId;
    public string $audio; // file_id | url | path to local file
    public ?string $caption;
    public ?string $slug;
    public ?array $options;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
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
     * @param string $caption
     * @return static
     */
    public function setCaption(string $caption): static
    {
        $this->caption = trim($caption);

        return $this;
    }


    /**
     * Добавляет массив видео к сообщению
     *
     * @param string $audio
     * @return static
     */
    public function setAudio(string $audio): static
    {
        $this->audio = trim($audio);

        return $this;
    }
}
