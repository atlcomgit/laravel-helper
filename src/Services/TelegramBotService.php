<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutResponseDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetWebhookDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;

/**
 * Сервис бота telegram
 */
class TelegramBotService extends DefaultService
{
    public function __construct(private TelegramApiService $telegramApiService) {}


    public function send(TelegramBotOutDto $dto): void
    {
        $dto->response = match ($dto::class) {
            TelegramBotOutSendMessageDto::class => $this->sendMessage($dto),
            TelegramBotOutSetWebhookDto::class => $this->setWebhook($dto),

            default => throw new LaravelHelperException('Не определен метод отправки сообщения'),
        };
    }


    public function sendMessage(TelegramBotOutSendMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            text: $dto->text,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    public function setWebhook(TelegramBotOutSetWebhookDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->setWebhook(
            botToken: $dto->token,
            url: $dto->url,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }
}
