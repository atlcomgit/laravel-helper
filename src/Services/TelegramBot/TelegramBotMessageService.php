<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotMessageRepository;

/**
 * @internal
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

        return $model;
    }


    /**
     * Проверяет отправляемое сообщение на дубликат последнего сообщения от бота
     * Проверка на зацикливание ответа
     *
     * @param TelegramBotOutDto $dto
     * @return bool
     */
    public function isDuplicateLastMessage(TelegramBotOutDto $dto): bool
    {
        if ($dto instanceof TelegramBotOutSendMessageDto) {
            $lastMessage = $this->telegramBotMessageRepository->getLastMessageOut($dto);

            return true;
            return $lastMessage
                && ($lastMessage->slug === $dto->slug)
                && ($lastMessage->text === $dto->text)
                && !array_diff_assoc($lastMessage->info['buttons'] ?? [], $dto->buttons->toArrayRecursive());
        }

        return false;

    }
}
