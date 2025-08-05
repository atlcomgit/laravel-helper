<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
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
            $lastMessage = $this->telegramBotMessageRepository->getLastMessage($dto);
            $result = $lastMessage
                && ($lastMessage->type === TelegramBotMessageTypeEnum::Outgoing)
                && ($lastMessage->slug === $dto->slug || strip_tags($lastMessage->text) === strip_tags($dto->text))
                && !array_diff_assoc($lastMessage->info['buttons'] ?? [], $dto->buttons->toArrayRecursive());

            !$result ?: telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Повторное сообщение бота отменено',
                'Сообщение' => $dto->onlyKeys(['externalChatId', 'slug', 'text']),
            ], TelegramTypeEnum::Warning);

            return $result;
        }

        return false;

    }
}
