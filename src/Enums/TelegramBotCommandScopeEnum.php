<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Enums;

use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Типы областей видимости команд бота Telegram (BotCommandScope)
 */
enum TelegramBotCommandScopeEnum: string
{
    use HelperEnumTrait;

    // Глобальные области
    case Default = 'default';
    case AllPrivateChats = 'all_private_chats';
    case AllGroupChats = 'all_group_chats';
    case AllChatAdministrators = 'all_chat_administrators';

    // Области, требующие дополнительных параметров (chat_id, user_id)
    case Chat = 'chat';
    case ChatAdministrators = 'chat_administrators';
    case ChatMember = 'chat_member';

    /**
     * Значение по умолчанию
     */
    public static function enumDefault(): mixed
    {
        return self::Default ->value;
    }


    public static function enumLabel(?BackedEnum $enum): ?string
    {
        return match ($enum) {
            self::Default => 'По умолчанию',
            self::AllPrivateChats => 'Все приватные чаты',
            self::AllGroupChats => 'Все групповые чаты',
            self::AllChatAdministrators => 'Все администраторы чатов',
            self::Chat => 'Конкретный чат',
            self::ChatAdministrators => 'Администраторы конкретного чата',
            self::ChatMember => 'Участник конкретного чата',
            default => null,
        };
    }
}
