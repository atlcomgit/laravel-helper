<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageStatusEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;

/**
 * Сервис слушателя событий телеграм бота
 */
class TelegramBotListenerService extends DefaultService
{
    public function __construct(private TelegramBotMessageService $telegramBotMessageService) {}


    /**
     * Обрабатывает входящие сообщения телеграм бота и сохраняет в БД
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
            'type' => TelegramBotMessageTypeEnum::Incoming,
            'status' => match (true) {
                (bool)$dto->message->replyToMessage => TelegramBotMessageStatusEnum::Reply,
                (bool)$dto->callbackQuery => TelegramBotMessageStatusEnum::Callback,

                default => TelegramBotMessageStatusEnum::New ,
            },
            'externalUpdateId' => $dto->updateId,
            'telegramBotChatId' => $telegramBotChat->id,
            'telegramBotUserId' => $telegramBotUser->id,
            'telegramBotMessageId' => $dto->message->replyToMessage
                ? $this->telegramBotMessageService->getByExternalMessageId($dto->message->replyToMessage)?->id
                : null,
            'info' => [
                ...($dto->callbackQuery ? ['callback' => $dto->callbackQuery->data] : []),
                ...($dto->message?->replyMarkup?->buttons ? ['buttons' => $dto->message->replyMarkup->buttons] : []),
            ],
        ]))->save();
    }


    /**
     * Обрабатывает исходящие сообщения телеграм бота и сохраняет в БД
     *
     * @param TelegramBotOutDto $dto
     * @return void
     */
    public function outgoing(TelegramBotOutDto $dto): void
    {
        $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->response->message->chat))
            ->save();

        $telegramBotUser = ($userDto = TelegramBotUserDto::create($dto->response->message->from))
            ->save();

        ($chatDto = TelegramBotMessageDto::create($dto->response->message, [
            'type' => TelegramBotMessageTypeEnum::Outgoing,
            'status' => TelegramBotMessageStatusEnum::New ,
            'slug' => $dto instanceof TelegramBotOutSendMessageDto ? $dto->slug : null,
            'externalUpdateId' => null,
            'telegramBotChatId' => $telegramBotChat->id,
            'telegramBotUserId' => $telegramBotUser->id,
            'telegramBotMessageId' => $dto->response->message->replyToMessage
                ? $this->telegramBotMessageService->getByExternalMessageId($dto->response->message->replyToMessage)?->id
                : null,
            'info' => [
                ...($dto->response->message?->replyMarkup?->buttons ? ['buttons' => $dto->response->message->replyMarkup->buttons] : []),
            ],
        ]))->save();
    }
}
