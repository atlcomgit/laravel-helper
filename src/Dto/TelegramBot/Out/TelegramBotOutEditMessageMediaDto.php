<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO редактирования медиа сообщения (editMessageMedia)
 */
class TelegramBotOutEditMessageMediaDto extends TelegramBotOutDto
{
    public string|int $externalChatId;
    public ?int       $messageId;
    public ?string    $inlineMessageId;
    public string     $mediaType;
    public string     $media;
    public ?string    $caption;
    public string     $parseMode;
    public ?array     $replyMarkup;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'chatId'          => null,
            'messageId'       => null,
            'inlineMessageId' => null,
            'mediaType'       => 'document',
            'media'           => '',
            'caption'         => null,
            'parseMode'       => 'HTML',
            'replyMarkup'     => null,
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
     * Указывает тип медиа для редактирования
     *
     * @param string $mediaType
     * @return static
     */
    public function setMediaType(string $mediaType): static
    {
        $this->mediaType = trim($mediaType);

        return $this;
    }


    /**
     * Указывает путь к медиа или file_id
     *
     * @param string $media
     * @return static
     */
    public function setMedia(string $media): static
    {
        $this->media = trim($media);

        return $this;
    }


    /**
     * Указывает подпись для медиа
     *
     * @param string|null $caption
     * @return static
     */
    public function setCaption(?string $caption): static
    {
        $this->caption = is_string($caption) ? trim($caption) : null;

        return $this;
    }
}
