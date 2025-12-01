<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Models;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\TelegramBotVariableTypeEnum;
use Atlcom\LaravelHelper\Exceptions\TelegramBotException;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotVariableService;
use Override;

/**
 * Dto модели переменной чата телеграм бота
 * @see TelegramBotVariable
 */
class TelegramBotVariableDto extends DefaultDto
{
    public ?int $id;
    public string $uuid;
    public int $telegramBotChatId;
    public ?int $telegramBotMessageId;
    public TelegramBotVariableTypeEnum $type;
    public string $group;
    public string $name;
    public mixed $value;


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
            'group' => 'default',
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
        return [];
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
        return TelegramBotVariable::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onFilling()
     */
    protected function onFilling(array &$array): void
    {
        $value = $array['value'] ?? null;
        $array['type'] ??= TelegramBotVariableTypeEnum::getType($value);
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
     */
    protected function onFilled(array $array): void
    {
        // Устраняем любые HTML-теги и преобразуем к безопасному sql значению
        is_null($this->value) ?: $this->value = Hlp::sqlSafeValue(strip_tags($this->value));
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
            ->for(TelegramBotVariable::class);
    }


    /**
     * Сохраняет пользователя телеграм бота в БД
     *
     * @return TelegramBotVariable
     */
    public function save(): TelegramBotVariable
    {
        return app(TelegramBotVariableService::class)->save($this)
            ?: TelegramBotException::except('Ошибка сохранения переменной чата в БД');
    }
}
