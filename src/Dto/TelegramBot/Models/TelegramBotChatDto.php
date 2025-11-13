<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Models;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Exceptions\TelegramBotException;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotChatService;
use Override;

/**
 * Dto модели чата телеграм бота
 * @see TelegramBotChat
 * 
 * @method TelegramBotChatDto info(?array $info)
 */
class TelegramBotChatDto extends DefaultDto
{
    public ?int $id;
    public string $uuid;
    public int $externalChatId;
    public string $name;
    public string $chatName;
    public string $type;
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
            'externalChatId' => 'id',
            'name' => 'firstName',
            'chatName' => 'userName',
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
        return TelegramBotChat::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
     */
    protected function onFilled(array $array): void
    {
        // Устраняем любые HTML-теги
        $this->name = strip_tags($this->name);

        // Преобразуем к безопасному sql значению
        $this->name = Hlp::sqlSafeValue($this->name);

        // Устраняем любые HTML-теги
        $this->chatName = strip_tags($this->chatName);

        // Преобразуем к безопасному sql значению
        $this->chatName = Hlp::sqlSafeValue($this->chatName);
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
            ->for(TelegramBotChat::class);
    }


    /**
     * Сохраняет пользователя телеграм бота в БД
     *
     * @return TelegramBotChat
     */
    public function save(): TelegramBotChat
    {
        return app(TelegramBotChatService::class)->save($this)
            ?: TelegramBotException::except('Ошибка сохранения чата в БД');
    }
}
