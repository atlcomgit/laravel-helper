<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Controllers;

use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Webhook\TelegramBotWebhookResponseDto;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TelegramBotController extends DefaultController
{
    /**
     * Обработчик вебхука бота телеграм
     *
     * @param TelegramBotInDto $dto
     * @return TelegramBotWebhookResponseDto
     */
    public function webhook(TelegramBotInDto $dto): Response|JsonResponse|TelegramBotWebhookResponseDto
    {
        event(new TelegramBotEvent($dto));

        return response()->json(TelegramBotWebhookResponseDto::create($dto)->toArray());
    }
}
