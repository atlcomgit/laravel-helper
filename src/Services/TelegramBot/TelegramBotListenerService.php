<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotMemberDto;
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
                'telegramBotMessageId' => match (true) {
                    (bool)$dto->message->replyToMessage => $this->telegramBotMessageService
                        ->getByExternalMessageId($dto->message->replyToMessage)?->id,
                    (bool)$dto->callbackQuery => $this->telegramBotMessageService
                        ->getByExternalMessageId($dto->message)?->id,

                    default => $this->telegramBotMessageService->getPreviousMessageOutgoing($dto->message)?->id,
                },
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
                'Exception' => Hlp::exceptionToArray($exception),
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
            if (!$dto->response->status || !$dto->response->message) {
                return;
            }

            $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->response->message->chat))
                ->save();

            $telegramBotChatInfoStatus = $telegramBotChat->info['status'] ?? null;
            if ($telegramBotChatInfoStatus === 'kicked') {
                return;
            }

            $telegramBotUser = ($userDto = TelegramBotUserDto::create($dto->response->message->from))
                ->save();

            $telegramBotMessage = ($chatDto = TelegramBotMessageDto::create($dto->response->message, [
                'type' => TelegramBotMessageTypeEnum::Outgoing,
                'status' => TelegramBotMessageStatusEnum::New ,
                'slug' => property_exists($dto, 'slug') ? $dto->slug : null,
                'externalUpdateId' => null,
                'telegramBotChatId' => $telegramBotChat->id,
                'telegramBotUserId' => $telegramBotUser->id,
                'telegramBotMessageId' => match (true) {
                    (bool)$dto->response->message->replyToMessage => $this->telegramBotMessageService
                        ->getByExternalMessageId($dto->response->message->replyToMessage)?->id,

                    default => $dto->previousMessageId,
                },
                'info' => [
                    ...(
                        $dto->response->message?->buttons
                        ? ['buttons' => $dto->response->message->buttons]
                        : []
                    ),
                    ...(
                        $dto->response->message?->replyMarkup?->buttons
                        ? ['buttons' => $dto->response->message->replyMarkup->buttons]
                        : []
                    ),
                    ...(
                        $dto->response->message?->keyboards
                        ? ['keyboards' => $dto->response->message->keyboards]
                        : []
                    ),
                    ...(
                        $dto->response->message?->replyMarkup?->keyboards
                        ? ['keyboards' => $dto->response->message->replyMarkup->keyboards]
                        : []
                    ),
                    ...(
                        $dto->response->message?->video
                        ? ['video' => $dto->response->message->video]
                        : []
                    ),
                ],
            ]))->save();

            event(new TelegramBotMessageEvent($telegramBotMessage));

        } catch (Throwable $exception) {
            telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Ошибка исходящего сообщения бота телеграм',
                'Сообщение' => $dto,
                'Exception' => Hlp::exceptionToArray($exception),
            ], TelegramTypeEnum::Error);
        }
    }


    public function member(TelegramBotMemberDto $dto): void
    {
        try {
            $telegramBotChat = ($chatDto = TelegramBotChatDto::create($dto->myChatMember->chat))
                ->info([
                    ...$telegramBotChat->info ?? [],
                    'status' => $dto->myChatMember->newChatMember->status,
                ])
                ->save();

        } catch (Throwable $exception) {
            telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Ошибка входящего информирования бота телеграм',
                'Сообщение' => $dto,
                'Exception' => Hlp::exceptionToArray($exception),
            ], TelegramTypeEnum::Error);
        }
    }
}
