<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Models;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Exceptions\TelegramBotException;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotUserService;
use Override;

/**
 * Dto модели пользователя телеграм бота
 * @see TelegramBotUser
 */
class TelegramBotUserDto extends DefaultDto
{
    public ?int $id;
    public string $uuid;
    public int $externalUserId;
    public string $firstName;
    public ?string $userName;
    public ?string $phone;
    public string $language;
    public ?bool $isBan;
    public ?bool $isBot;
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
            'isBan' => false,
            'isBot' => null,
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
            'externalUserId' => 'id',
            'firstName' => 'first_name',
            'userName' => 'user_name',
            'language' => 'languageCode',
            'isBan' => 'is_ban',
            'isBot' => 'is_bot',
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
        return TelegramBotUser::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
     */
    protected function onFilled(array $array): void
    {
        // Устраняем любые HTML-теги
        $this->firstName = strip_tags($this->firstName);

        // Преобразуем к безопасному sql значению
        $this->firstName = Hlp::sqlSafeValue($this->firstName);

        // Устраняем любые HTML-теги
        $this->userName = strip_tags($this->userName);

        // Преобразуем к безопасному sql значению
        $this->userName = Hlp::sqlSafeValue($this->userName);
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
            ->for(TelegramBotUser::class);
    }


    /**
     * Сохраняет пользователя телеграм бота в БД
     *
     * @return TelegramBotUser
     */
    public function save(): TelegramBotUser
    {
        return app(TelegramBotUserService::class)->save($this)
            ?: TelegramBotException::except('Ошибка сохранения пользователя в БД');
    }
}
