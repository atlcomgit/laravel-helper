<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutResponseDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetWebhookDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Services\TelegramApiService;

/**
 * Сервис бота telegram
 */
class TelegramBotService extends DefaultService
{
    public function __construct(private TelegramApiService $telegramApiService) {}


    /**
     * Отправка сообщения по переданному dto
     *
     * @param TelegramBotOutDto $dto
     * @return void
     */
    public function send(TelegramBotOutDto $dto): void
    {
        $dto->response = match ($dto::class) {
            TelegramBotOutSendMessageDto::class => $this->sendMessage($dto),
            TelegramBotOutSetWebhookDto::class => $this->setWebhook($dto),

            default => throw new LaravelHelperException('Не определен метод отправки сообщения'),
        };

        event(new TelegramBotEvent($dto));
    }


    protected function sendMessage(TelegramBotOutSendMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            text: $dto->text,
            options: [
                ...(
                    ($dto->buttons && $dto->buttons->isNotEmpty())
                    ? ['reply_markup' => json_encode(['inline_keyboard' => $dto->buttons->toArrayRecursive()])]
                    : []
                ),
            ],
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function setWebhook(TelegramBotOutSetWebhookDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->setWebhook(
            botToken: $dto->token,
            url: $dto->url,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }
}
