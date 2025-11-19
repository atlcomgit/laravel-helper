<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotButtonTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Traits\TelegramBotKeyboardTrait;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO отправки документов (sendDocument)
 */
class TelegramBotOutSendDocumentDto extends TelegramBotOutDto
{
    use TelegramBotButtonTrait;
    use TelegramBotKeyboardTrait;


    public string|int $externalChatId;
    public string $document; // file_id | url | path to local file
    public ?string $caption;
    public ?string $slug;
    public ?array $options;


    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'document' => '',
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
     * Добавляет подпись к сообщению
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
     * Указывает отправляемый документ
     *
     * @param string $document
     * @return static
     */
    public function setDocument(string $document): static
    {
        $this->document = trim($document);

        return $this;
    }
}
