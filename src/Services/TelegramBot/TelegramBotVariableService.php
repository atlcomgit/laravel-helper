<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotVariableRepository;

/**
 * @internal
 * Сервис переменной чата телеграм бота
 */
class TelegramBotVariableService extends DefaultService
{
    public function __construct(private TelegramBotVariableRepository $telegramBotVariableRepository) {}


    /**
     * Сохраняет сообщение телеграм бота
     *
     * @param TelegramBotVariableDto $dto
     * @return TelegramBotVariable
     */
    public function save(TelegramBotVariableDto $dto): TelegramBotVariable
    {
        $model = $this->telegramBotVariableRepository->updateOrCreate($dto);

        return $model;
    }


    /**
     * Возвращает значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @param string $name
     * @return mixed
     */
    public function getVariable(TelegramBotMessage $message, string $group, string $name): mixed
    {
        return $message->telegramBotChat?->telegramBotVariables
            ->where('group', $group)
            ->where('name', $name)
            ->first()?->value;
    }


    /**
     * Устанавливает значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param string $group
     * @param string $name
     * @param mixed $value
     * @return TelegramBotVariable
     */
    public function setVariable(TelegramBotMessage $message, string $group, string $name, mixed $value): TelegramBotVariable
    {
        // Удаляем переменную чата для истории
        $message->telegramBotChat?->telegramBotVariables
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
}
