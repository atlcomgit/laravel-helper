<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonUrlDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutMenuButtonDto;
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
     * Возвращает последнее сообщение от бота
     *
     * @param TelegramBotInMessageDto $dto
     * @return TelegramBotMessage|null
     */
    public function getPreviousMessageOutgoing(TelegramBotInMessageDto $dto): ?TelegramBotMessage
    {
        return $this->telegramBotMessageRepository->getPreviousMessageOutgoing($dto);
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
            $lastMessageIn = $this->telegramBotMessageRepository->getById($dto->previousMessageId);
            $lastMessageOut = $this->telegramBotMessageRepository->getLastMessageOutgoing($dto);
            $result = $lastMessageOut
                && !($lastMessageIn->text === '/start')
                && ($lastMessageOut->type === TelegramBotMessageTypeEnum::Outgoing)
                && (
                    $lastMessageOut->slug === $dto->slug
                    || strip_tags($lastMessageOut->text) === strip_tags($dto->text)
                )
                && !array_diff_assoc($lastMessageOut->info['buttons'] ?? [], $dto->buttons->toArrayRecursive());

            !($result && isLocal()) ?: telegram([
                'Бот' => Lh::config(ConfigEnum::TelegramBot, 'name'),
                'Событие' => 'Повторное сообщение бота отменено',
                'Сообщение' => $dto->onlyKeys(['externalChatId', 'slug', 'text']),
            ], TelegramTypeEnum::Warning);

            return $result;
        }

        return false;

    }


    /**
     * Создает массив inline кнопок из dto
     *
     * @param array $buttons
     * @return array
     */
    public function prepareButtons(array $buttons): array
    {
        $buttons = array_map(
            fn ($button) => match (true) {
                $button instanceof TelegramBotOutDataButtonCallbackDto => $button,
                $button instanceof TelegramBotOutDataButtonUrlDto => $button,

                is_array($button) && !is_scalar(Hlp::arrayFirst($button)) => $this->prepareButtons($button),

                is_array($button) && isset($button['text']) && isset($button['callback'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['callbackData'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['callback_data'])
                => TelegramBotOutDataButtonCallbackDto::create($button),
                is_array($button) && isset($button['text']) && isset($button['url'])
                => TelegramBotOutDataButtonUrlDto::create($button),

                default => null,
            },
            $buttons,
        );

        return array_filter($buttons);
    }


    /**
     * Создает массив keyboard кнопок из dto
     *
     * @param array $keyboards
     * @return array
     */
    public function prepareKeyboards(array $keyboards): array
    {
        $keyboards = array_map(
            fn ($keyboard) => match (true) {
                $keyboard instanceof TelegramBotOutMenuButtonDto => $keyboard,

                is_array($keyboard) && !is_scalar(Hlp::arrayFirst($keyboard)) => $this->prepareKeyboards($keyboard),

                is_array($keyboard) && isset($keyboard['text'])
                => TelegramBotOutMenuButtonDto::create($keyboard),

                default => null,
            },
            $keyboards,
        );

        return array_filter($keyboards);
    }
}
