<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto;
use Atlcom\LaravelHelper\Events\TelegramBotChatEvent;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotChatRepository;

/**
 * @internal
 * Сервис чата телеграм бота
 */
class TelegramBotChatService extends DefaultService
{
    public function __construct(private TelegramBotChatRepository $telegramBotChatRepository) {}


    /**
     * Сохраняет чат телеграм бота
     *
     * @param TelegramBotChatDto $dto
     * @return TelegramBotChat
     */
    public function save(TelegramBotChatDto $dto): TelegramBotChat
    {
        $chat = $this->telegramBotChatRepository->updateOrCreate($dto);

        !($chat->wasRecentlyCreated || $chat->wasChanged()) ?: event(new TelegramBotChatEvent($chat));

        return $chat;
    }
}