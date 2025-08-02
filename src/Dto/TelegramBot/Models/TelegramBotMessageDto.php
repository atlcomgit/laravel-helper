<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Models;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Exceptions\TelegramBotException;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Carbon\Carbon;
use Override;

/**
 * Dto модели пользователя телеграм бота
 */
class TelegramBotMessageDto extends DefaultDto
{
    public ?int $id;
    public string $uuid;
    public int $externalMessageId;
    public int $externalUpdateId;
    public int $telegramBotChatId;
    public int $telegramBotUserId;
    public ?int $telegramBotMessageId;
    public string $text;
    public Carbon $sendAt;
    public ?Carbon $editAt;
    public ?array $info;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    #[Override()]
    protected function defaults(): array
    {
        return [
            'uuid' => uuid(),
        ];
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     *
     * @return array
     */
    #[Override()]
    protected function mappings(): array
    {
        return [
            'externalMessageId' => 'messageId',
            'externalUpdateId' => 'updateId',
            'sendAt' => 'date',
            'editAt' => 'editDate',
        ];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     *
     * @return array
     */
    #[Override()]
    protected function casts(): array
    {
        return TelegramBotMessage::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onSerializing()
     *
     * @return array
     */
    #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->serializeKeys(true)
            ->onlyNotNull()
            ->mappingKeys($this->mappings())
            ->for(TelegramBotMessage::class);
    }


    /**
     * Сохраняет пользователя телеграм бота в БД
     *
     * @return TelegramBotMessage
     */
    public function save(): TelegramBotMessage
    {
        return app(TelegramBotMessageService::class)->save($this)
            ?: TelegramBotException::except('Ошибка сохранения сообщения в БД');
    }
}
