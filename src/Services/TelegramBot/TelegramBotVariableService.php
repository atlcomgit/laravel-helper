<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotVariableRepository;
use BackedEnum;
use Illuminate\Support\Collection;

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
     * Возвращает коллекция переменных по группе чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param BackedEnum|string $group
     * @return Collection<TelegramBotVariable>
     */
    public function getGroupVariables(TelegramBotMessage $message, BackedEnum|string $group): Collection
    {
        !($group instanceof BackedEnum) ?: $group = $group->value;

        return $this->telegramBotVariableRepository->getMessageGroupVariables($message, $group);
    }


    /**
     * Возвращает значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param BackedEnum|string $group
     * @param BackedEnum|string $name
     * @return mixed
     */
    public function getVariable(TelegramBotMessage $message, BackedEnum|string $group, BackedEnum|string $name): mixed
    {
        !($group instanceof BackedEnum) ?: $group = $group->value;
        !($name instanceof BackedEnum) ?: $name = $name->value;

        return $this->telegramBotVariableRepository->getMessageVariable($message, $group, $name)?->value;
    }


    /**
     * Устанавливает значение переменной чата телеграм бота
     *
     * @param TelegramBotMessage $message
     * @param BackedEnum|string $group
     * @param BackedEnum|string $name
     * @param mixed $value
     * @return TelegramBotVariable
     */
    public function setVariable(
        TelegramBotMessage $message,
        BackedEnum|string $group,
        BackedEnum|string $name,
        mixed $value,
    ): TelegramBotVariable {
        !($group instanceof BackedEnum) ?: $group = $group->value;
        !($name instanceof BackedEnum) ?: $name = $name->value;

        return $this->telegramBotVariableRepository->setMessageVariable($message, $group, $name, $value);
    }
}
