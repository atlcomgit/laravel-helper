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
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageStatusEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Events\TelegramBotMessageEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Throwable;

/**
 * @internal
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
        try {
            $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->message->chat))
                ->save();

            $telegramBotUser = ($userDto = TelegramBotUserDto::create($dto->message->from))
                ->save();

            $telegramBotMessage = ($chatDto = TelegramBotMessageDto::create($dto->message, [
                'type' => TelegramBotMessageTypeEnum::Incoming,
                'status' => match (true) {
                    (bool)$dto->message->replyToMessage => TelegramBotMessageStatusEnum::Reply,
                    (bool)$dto->callbackQuery => TelegramBotMessageStatusEnum::Callback,
                    (bool)$dto->message->editDate => TelegramBotMessageStatusEnum::Update,

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

            event(new TelegramBotMessageEvent($telegramBotMessage));

        } catch (Throwable $exception) {
            telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Ошибка входящего сообщения бота телеграм',
                'Сообщение' => $dto,
            ], TelegramTypeEnum::Error);
        }
    }


    /**
     * Обрабатывает исходящие сообщения телеграм бота и сохраняет в БД
     *
     * @param TelegramBotOutDto $dto
     * @return void
     */
    public function outgoing(TelegramBotOutDto $dto): void
    {
        try {
            if (!$dto->response->status) {
                return;
            }

            $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->response->message->chat))
                ->save();

            $telegramBotUser = ($userDto = TelegramBotUserDto::create($dto->response->message->from))
                ->save();

            $telegramBotMessage = ($chatDto = TelegramBotMessageDto::create($dto->response->message, [
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

            event(new TelegramBotMessageEvent($telegramBotMessage));

        } catch (Throwable $exception) {
            telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Ошибка исходящего сообщения бота телеграм',
                'Сообщение' => $dto->onlyKeys(['externalChatId', 'slug', 'text']),
            ], TelegramTypeEnum::Error);
        }
    }
}
