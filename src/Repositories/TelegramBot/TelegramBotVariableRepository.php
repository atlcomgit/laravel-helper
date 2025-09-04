<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;

/**
 * @internal
 * Репозиторий переменной чата телеграм бота
 */
class TelegramBotVariableRepository extends DefaultRepository
{
    public function __construct(
        /** @var TelegramBotVariable */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::TelegramBot, 'model_variable') ?? TelegramBotVariable::class;
    }


    /**
     * Возвращает сообщения по id
     *
     * @param int $variableId
     * @return TelegramBotVariable|null
     */
    public function getById(int $variableId): ?TelegramBotVariable
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                // ->withTrashed()
                ->find($variableId)
        );
    }


    /**
     * Возвращает сообщение по внешнему external_message_id
     *
     * @param int $externalMessageId
     * @param string $group
     * @param string $name
     * @return TelegramBotVariable|null
     */
    public function getByTelegramBotChatIdAndGroupAndName(
        int $telegramBotChatId,
        string $group,
        string $name,
    ): ?TelegramBotVariable {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->where('telegram_bot_chat_id', $telegramBotChatId)
                ->where('group', $group)
                ->where('name', $name)
                ->first()
        );
    }


    /**
     * Создает или обновляет переменную чата телеграм бота в БД
     *
     * @param TelegramBotVariableDto $dto
     * @return TelegramBotVariable
     */
    public function updateOrCreate(TelegramBotVariableDto $dto): TelegramBotVariable
    {
        return $this->withoutTelescope(function () use ($dto) {
            ($model = $this->getByTelegramBotChatIdAndGroupAndName($dto->telegramBotChatId, $dto->group, $dto->name))
                ? $model->update([
                    'telegram_bot_message_id' => $dto->telegramBotMessageId,
                    'type' => $dto->type,
                    'value' => $dto->value,
                ])
                : $model = $this->model::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->create([
                        'uuid' => $dto->uuid,
                        'telegram_bot_chat_id' => $dto->telegramBotChatId,
                        'telegram_bot_message_id' => $dto->telegramBotMessageId,
                        'type' => $dto->type,
                        'name' => $dto->name,
                        'value' => $dto->value,
                    ]);

            return $model;
        });
    }
}
