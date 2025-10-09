<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Illuminate\Support\Collection;

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
                    'group' => $dto->group,
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
                        'group' => $dto->group,
                        'name' => $dto->name,
                        'value' => $dto->value,
                    ]);

            return $model;
        });
    }


    /**
     * Возвращает коллекция переменных по группе чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @return Collection<TelegramBotVariable>
     */
    public function getMessageGroupVariables(TelegramBotMessage $message, string $group): Collection
    {
        return $message->refresh()->telegramBotChat?->telegramBotVariables
            ->where('group', $group);
    }


    /**
     * Удаляет переменные по группе чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @return void
     */
    public function delMessageGroupVariables(TelegramBotMessage $message, string $group): void
    {
        $message->refresh()->telegramBotChat?->telegramBotVariables()
            ->where('group', $group)
            ->delete();
    }


    /**
     * Возвращает значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @param string $name
     * @return TelegramBotVariable|null
     */
    public function getMessageVariable(TelegramBotMessage $message, string $group, string $name): ?TelegramBotVariable
    {
        return $message->refresh()->telegramBotChat?->telegramBotVariables
            ->where('group', $group)
            ->where('name', $name)
            ->first();
    }


    /**
     * Устанавливает значение переменной чата телеграм бота и удаляет предыдущее значение через softDelete
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @param string $name
     * @param mixed $value
     * @return TelegramBotVariable
     */
    public function setMessageVariable(
        TelegramBotMessage $message,
        string $group,
        string $name,
        mixed $value,
    ): TelegramBotVariable {
        // Удаляем переменную чата для истории
        $message->refresh()->telegramBotChat?->telegramBotVariables
            ->where('group', $group)
            ->where('name', $name)
            ->first()?->delete();

        return $telegramBotVariableDto = TelegramBotVariableDto::create(
            telegramBotChatId: $message->telegramBotChat->id,
            telegramBotMessageId: $message->id,
            group: $group,
            name: $name,
            value: $value,
        )->save();
    }


    /**
     * Удаляет значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @param string $name
     * @return void
     */
    public function delMessageVariable(
        TelegramBotMessage $message,
        string $group,
        string $name,
    ): void {
        $message->refresh()->telegramBotChat?->telegramBotVariables
            ->where('group', $group)
            ->where('name', $name)
            ->first()?->delete();
    }
}
