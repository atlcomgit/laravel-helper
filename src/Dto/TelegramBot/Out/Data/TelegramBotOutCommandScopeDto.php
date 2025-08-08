<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\TelegramBotCommandScopeEnum;

/**
 * DTO области видимости команд бота Telegram (BotCommandScope)
 */
class TelegramBotOutCommandScopeDto extends DefaultDto
{
    public TelegramBotCommandScopeEnum $type;
    public ?int $chatId;
    public ?int $userId;

    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'type' => TelegramBotCommandScopeEnum::Default ,
            'chatId' => null,
            'userId' => null,
        ];
    }


    /**
     * Удобные сеттеры
     */
    public function forChat(int $chatId): static
    {
        $this->type = TelegramBotCommandScopeEnum::Chat;
        $this->chatId = $chatId;
        $this->userId = null;

        return $this;
    }


    public function forChatAdministrators(int $chatId): static
    {
        $this->type = TelegramBotCommandScopeEnum::ChatAdministrators;
        $this->chatId = $chatId;
        $this->userId = null;
        return $this;
    }


    public function forChatMember(int $chatId, int $userId): static
    {
        $this->type = TelegramBotCommandScopeEnum::ChatMember;
        $this->chatId = $chatId;
        $this->userId = $userId;
        return $this;
    }
}
