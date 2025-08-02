<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * Сервис слушателя событий телеграм бота
 */
class TelegramBotListenerService extends DefaultService
{
    public function __construct(private TelegramBotMessageService $telegramBotMessageService) {}


    /**
     * Обрабатывает входящие сообщения телеграм бота
     *
     * @param TelegramBotInDto $dto
     * @return void
     */
    public function incoming(TelegramBotInDto $dto): void
    {
        $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->message->chat))
            ->save();
        $telegramBotUser = ($userDto = TelegramBotUserDto::create($dto->message->from))
            ->save();
        ($chatDto = TelegramBotMessageDto::create($dto->message, [
            'externalUpdateId' => $dto->updateId,
            'telegramBotChatId' => $telegramBotChat->id,
            'telegramBotUserId' => $telegramBotUser->id,
            'telegramBotMessageId' => $dto->message->replyToMessage
                ? $this->telegramBotMessageService->getByExternalMessageId($dto->message->replyToMessage)?->id
                : null,
        ]))->save();
    }


    /**
     * Обрабатывает исходящие сообщения телеграм бота
     *
     * @param TelegramBotOutDto $dto
     * @return void
     */
    public function outgoing(TelegramBotOutDto $dto): void {}
}
