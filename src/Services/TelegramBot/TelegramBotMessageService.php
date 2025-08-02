<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Events\TelegramBotMessageEvent;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotMessageRepository;

/**
 * Сервис сообщения телеграм бота
 */
class TelegramBotMessageService extends DefaultService
{
    public function __construct(private TelegramBotMessageRepository $telegramBotMessageRepository) {}


    /**
     * Возвращает модель по внешнему Id
     *
     * @param TelegramBotInMessageDto $dto
     * @return TelegramBotMessage|null
     */
    public function getByExternalMessageId(TelegramBotInMessageDto $dto): ?TelegramBotMessage
    {
        return $this->telegramBotMessageRepository->getByExternalMessageId($dto->messageId);
    }


    /**
     * Сохраняет сообщение телеграм бота
     *
     * @param TelegramBotMessageDto $dto
     * @return TelegramBotMessage
     */
    public function save(TelegramBotMessageDto $dto): TelegramBotMessage
    {
        $model = $this->telegramBotMessageRepository->updateOrCreate($dto);

        event(new TelegramBotMessageEvent($model));

        return $model;
    }
}
