<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotChat;

/**
 * @internal
 * Репозиторий чата телеграм бота
 */
class TelegramBotChatRepository extends DefaultRepository
{
    public function __construct(
        /** @var TelegramBotChat */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::TelegramBot, 'model_chat') ?? TelegramBotChat::class;
    }


    /**
     * Возвращает чат по внешнему Id
     *
     * @param int $externalChatId
     * @return TelegramBotChat|null
     */
    public function getByExternalChatId(int $externalChatId): ?TelegramBotChat
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofExternalChatId($externalChatId)
                ->first()
        );
    }


    /**
     * Создает или обновляет чат телеграм бота в БД
     *
     * @param TelegramBotChatDto $dto
     * @return TelegramBotChat
     */
    public function updateOrCreate(TelegramBotChatDto $dto): TelegramBotChat
    {
        return $this->withoutTelescope(function () use ($dto) {
            ($model = $this->getByExternalChatId($dto->externalChatId))
                ? $model->update([
                    'name' => $dto->name,
                    'chat_name' => $dto->chatName,
                    'type' => $dto->type,
                ])
                : $model = $this->model::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->create([
                        'uuid' => $dto->uuid,
                        'external_chat_id' => $dto->externalChatId,
                        'name' => $dto->name,
                        'chat_name' => $dto->chatName,
                        'type' => $dto->type,
                        'info' => $dto->info,
                    ]);

            return $model;
        });
    }
}
