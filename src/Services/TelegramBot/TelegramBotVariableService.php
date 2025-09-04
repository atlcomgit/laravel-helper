<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotVariableRepository;

/**
 * @internal
 * Сервис переменной чата телеграм бота
 */
class TelegramBotVariableService extends DefaultService
{
    public function __construct(
        private TelegramBotVariableRepository $telegramBotVariableRepository,
    ) {}


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
}
