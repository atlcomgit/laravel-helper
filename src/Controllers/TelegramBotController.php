<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Controllers;

use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotInDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotMemberDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Webhook\TelegramBotWebhookResponseDto;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramBotController extends DefaultController
{
    /**
     * Обработчик вебхука бота телеграм
     *
     * @param Request $request
     * @return TelegramBotWebhookResponseDto
     */
    public function webhook(Request $request): Response|JsonResponse|TelegramBotWebhookResponseDto
    {
        $dto = match (true) {
            isset($request->message),
            isset($request->edited_message),
            isset($request->callback_query)
            => TelegramBotInDto::create($request),

            isset($request->my_chat_member)
            => TelegramBotMemberDto::create($request),

            default => null,
        };

        $dto
            ? event(new TelegramBotEvent($dto))
            : telegram([
                'TelegramBot' => 'Нераспознанный webhook',
                'Request' => $request->all(),
            ], TelegramTypeEnum::Warning);

        return response()->json(TelegramBotWebhookResponseDto::create($dto)->toArray());
    }
}
