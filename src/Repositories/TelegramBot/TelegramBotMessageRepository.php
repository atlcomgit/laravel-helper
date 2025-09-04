<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageStatusEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Illuminate\Database\Eloquent\Collection;

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
                // ->withTrashed()
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
                // ->withTrashed()
                ->ofExternalMessageId($externalMessageId)
                ->when($type, static fn ($q, $v) => $q->ofType($v))
                ->first()
        );
    }


    /**
     * Возвращает коллекцию сообщений по внешним external_message_id
     *
     * @param array<int> $externalMessageIds
     * @param bool $withTrashed
     * @return Collection<TelegramBotMessage>
     */
    public function getByExternalMessageIds(array $externalMessageIds, bool $withTrashed = false): Collection
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereIn('external_message_id', $externalMessageIds)
                // ->when($withTrashed, static fn ($q) => $q->withTrashed())
                ->get()
        );
    }


    /**
     * Возвращает коллекцию сообщений по внешним external_message_id
     *
     * @param array<int> $ids
     * @param bool $withTrashed
     * @return Collection<TelegramBotMessage>
     */
    public function getByIds(array $ids, bool $withTrashed = false): Collection
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereIn('id', $ids)
                // ->when($withTrashed, static fn ($q) => $q->withTrashed())
                ->get()
        );
    }


    /**
     * Удаляет сообщения по внешним external_message_id
     *
     * @param array<int> $externalMessageIds
     * @return int
     */
    public function deleteByExternalMessageIds(array $externalMessageIds): int
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereIn('external_message_id', $externalMessageIds)
                ->where('status', '!=', TelegramBotMessageStatusEnum::Delete)
                ->update(['status' => TelegramBotMessageStatusEnum::Delete/*, 'deleted_at' => now()*/])
        );
    }


    /**
     * Возвращает последнее сообщение бота
     *
     * @param TelegramBotOutDto $dto
     * @return TelegramBotMessage|null
     */
    public function getLatestMessage(TelegramBotOutDto $dto): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => property_exists($dto, 'externalChatId')
            ? $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                // ->withTrashed()
                ->whereHas('telegramBotChat', static fn ($q) => $q->where('external_chat_id', $dto->externalChatId))
                ->orderByDesc('id')
                ->first()
            : null
        );
    }


    /**
     * Возвращает предыдущее исходящее сообщение бота по 
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
                // ->withTrashed()
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
     * @param TelegramBotOutDto $dto
     * @return TelegramBotMessage|null
     */
    public function getLastMessageOutgoing(TelegramBotOutDto $dto): ?TelegramBotMessage
    {
        return $this->withoutTelescope(
            fn () => property_exists($dto, 'externalChatId')
            ? $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                // ->withTrashed()
                ->whereHas('telegramBotChat', static fn ($q) => $q->where('external_chat_id', $dto->externalChatId))
                ->ofType(TelegramBotMessageTypeEnum::Outgoing)
                ->whereNotIn('slug', ['', 'unknown', 'undefined', 'unrecognized', 'none', 'null'])
                ->orderByDesc('id')
                ->first()
            : null
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
                    'external_message_id' => $dto->externalMessageId,
                    'external_update_id' => $dto->externalUpdateId,
                    'telegram_bot_chat_id' => $dto->telegramBotChatId,
                    'telegram_bot_user_id' => $dto->telegramBotUserId,
                    'telegram_bot_message_id' => $dto->telegramBotMessageId,
                    'status' => $dto->status,
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
                        'external_message_id' => $dto->externalMessageId,
                        'external_update_id' => $dto->externalUpdateId,
                        'telegram_bot_chat_id' => $dto->telegramBotChatId,
                        'telegram_bot_user_id' => $dto->telegramBotUserId,
                        'telegram_bot_message_id' => $dto->telegramBotMessageId,
                        'type' => $dto->type,
                        'status' => $dto->status,
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
