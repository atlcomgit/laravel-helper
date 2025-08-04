<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;

/**
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
     * Возвращает сообщение по внешнему Id
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
