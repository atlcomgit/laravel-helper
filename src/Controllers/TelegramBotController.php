<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Controllers;

use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Webhook\TelegramBotWebhookResponseDto;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;

class TelegramBotController extends DefaultController
{
    /**
     * Обработчик вебхука бота телеграм
     *
     * @param TelegramBotInDto $dto
     * @return TelegramBotWebhookResponseDto
     */
    public function webhook(TelegramBotInDto $dto): TelegramBotWebhookResponseDto
    {
        event(new TelegramBotEvent($dto));

        return TelegramBotWebhookResponseDto::create();
    }
}
