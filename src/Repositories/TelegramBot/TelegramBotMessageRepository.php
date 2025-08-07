<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;

/**
 * @internal
 * Репозиторий сообщения телеграм бота
 */
class TelegramBotMessageRepository extends DefaultRepository
{
    public function __construct(
        /** @var TelegramBotMessage */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::TelegramBot, 'model_message') ?? TelegramBotMessage::class;
    }


    /**
     * Возвращает сообщения по id
     *
     * @param int $messageId
     * @return TelegramBotMessage|null
     */
    public function getById(int $messageId): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->find($messageId)
        );
    }


    /**
     * Возвращает сообщение по внешнему external_message_id
     *
     * @param int $externalMessageId
     * @return TelegramBotMessage|null
     */
    public function getByExternalMessageId(int $externalMessageId, ?TelegramBotMessageTypeEnum $type = null): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofExternalMessageId($externalMessageId)
                ->when($type, static fn ($q, $v) => $q->ofType($v))
                ->first()
        );
    }


    /**
     * Возвращает последнее исходящее сообщение бота по 
     *
     * @param TelegramBotInMessageDto $dto
     * @return TelegramBotMessage|null
     */
    public function getPreviousMessageOutgoing(TelegramBotInMessageDto $dto): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereHas('telegramBotChat', static fn ($q) => $q->where('external_chat_id', $dto->chat->id))
                ->ofType(TelegramBotMessageTypeEnum::Outgoing)
                ->whereNotIn('slug', ['', 'unknown', 'undefined', 'unrecognized', 'none', 'null'])
                ->orderByDesc('id')
                ->first()
        );
    }


    /**
     * Возвращает последнее исходящее сообщение бота
     *
     * @param TelegramBotOutSendMessageDto $dto
     * @return TelegramBotMessage|null
     */
    public function getLastMessageOutgoing(TelegramBotOutSendMessageDto $dto): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereHas('telegramBotChat', static fn ($q) => $q->where('external_chat_id', $dto->externalChatId))
                ->ofType(TelegramBotMessageTypeEnum::Outgoing)
                ->whereNotIn('slug', ['', 'unknown', 'undefined', 'unrecognized', 'none', 'null'])
                ->orderByDesc('id')
                ->first()
        );
    }


    /**
     * Создает или обновляет сообщение телеграм бота в БД
     *
     * @param TelegramBotMessageDto $dto
     * @return TelegramBotMessage
     */
    public function updateOrCreate(TelegramBotMessageDto $dto): TelegramBotMessage
    {
        return $this->withoutTelescope(function () use ($dto) {
            ($model = $this->getByExternalMessageId($dto->externalMessageId, $dto->type))
                ? $model->update([
                    'status' => $dto->status,
                    'external_message_id' => $dto->externalMessageId,
                    'external_update_id' => $dto->externalUpdateId,
                    'telegram_bot_chat_id' => $dto->telegramBotChatId,
                    'telegram_bot_user_id' => $dto->telegramBotUserId,
                    'telegram_bot_message_id' => $dto->telegramBotMessageId,
                    ...($dto->slug ? ['slug' => $dto->slug] : []),
                    'text' => $dto->text,
                    'send_at' => $dto->sendAt,
                    'edit_at' => $dto->editAt,
                    'info' => [
                        ...($model->info ?? []),
                        ...($dto->info ?? []),
                        ...(
                            (
                                $dto->externalUpdateId
                                && $model->external_update_id != $dto->externalUpdateId
                                && !in_array($dto->externalUpdateId, $model->info['update_ids'] ?? [])
                            )
                            ? ['update_ids' => [...($model->info['update_ids'] ?? []), $dto->externalUpdateId]]
                            : []
                        ),
                    ],
                ])
                : $model = $this->model::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->create([
                        'uuid' => $dto->uuid,
                        'type' => $dto->type,
                        'status' => $dto->status,
                        'external_message_id' => $dto->externalMessageId,
                        'external_update_id' => $dto->externalUpdateId,
                        'telegram_bot_chat_id' => $dto->telegramBotChatId,
                        'telegram_bot_user_id' => $dto->telegramBotUserId,
                        'telegram_bot_message_id' => $dto->telegramBotMessageId,
                        'slug' => $dto->slug,
                        'text' => $dto->text,
                        'send_at' => $dto->sendAt,
                        'edit_at' => $dto->editAt,
                        'info' => [
                            ...($dto->info ?? []),
                            ...($dto->externalUpdateId ? ['update_ids' => [$dto->externalUpdateId]] : []),
                        ],
                    ]);

            return $model;
        });
    }
}
