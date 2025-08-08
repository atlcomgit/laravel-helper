<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutResponseDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutUnsetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutGetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetWebhookDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutForwardMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutCopyMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutDeleteMessageDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutEditMessageTextDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendPhotoDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendVideoDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSendAudioDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Services\TelegramApiService;
use Throwable;

/**
 * @internal
 * Сервис бота telegram
 */
class TelegramBotService extends DefaultService
{
    public function __construct(
        private TelegramApiService $telegramApiService,
        private TelegramBotMessageService $telegramBotMessageService,
    ) {}


    /**
     * Отправка сообщения по переданному dto
     *
     * @param TelegramBotOutDto $dto
     * @return void
     */
    public function send(TelegramBotOutDto $dto): void
    {
        try {
            $dto->response = $this->telegramBotMessageService->isDuplicateLastMessage($dto)
                ? TelegramBotOutResponseDto::create(
                    status: false,
                    description: 'Повторное сообщение',
                )
                : match ($dto::class) {
                    TelegramBotOutSendMessageDto::class => $this->sendMessage($dto),
                    TelegramBotOutSetWebhookDto::class => $this->setWebhook($dto),
                    TelegramBotOutSetMyCommandsDto::class => $this->setMyCommands($dto),
                    TelegramBotOutUnsetMyCommandsDto::class => $this->unsetMyCommands($dto),
                    TelegramBotOutGetMyCommandsDto::class => $this->getMyCommands($dto),
                    TelegramBotOutForwardMessageDto::class => $this->forwardMessage($dto),
                    TelegramBotOutCopyMessageDto::class => $this->copyMessage($dto),
                    TelegramBotOutDeleteMessageDto::class => $this->deleteMessage($dto),
                    TelegramBotOutEditMessageTextDto::class => $this->editMessageText($dto),
                    TelegramBotOutSendPhotoDto::class => $this->sendPhoto($dto),
                    TelegramBotOutSendVideoDto::class => $this->sendVideo($dto),
                    TelegramBotOutSendAudioDto::class => $this->sendAudio($dto),

                    default => throw new LaravelHelperException('Не определен метод отправки сообщения'),
                };

        } catch (Throwable $exception) {
            $dto->response = TelegramBotOutResponseDto::create(
                status: false,
                description: $exception->getMessage(),
            );

            telegram($exception);

        } finally {
            event(new TelegramBotEvent($dto));
        }
    }


    protected function sendMessage(TelegramBotOutSendMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            text: $dto->text,
            options: [
                ...(
                    ($dto->buttons?->isNotEmpty() || $dto->keyboards?->isNotEmpty())
                    ? [
                        'reply_markup' => json_encode([
                            ...($dto->removeKeyboard ? ['remove_keyboard' => true] : []),
                            ...(
                                $dto->buttons?->isNotEmpty()
                                ? [
                                    'inline_keyboard' => $dto->buttons->toArrayRecursive(),
                                ]
                                : []
                            ),
                            ...(
                                $dto->keyboards?->isNotEmpty()
                                ? [
                                    'keyboard' => $dto->keyboards->toArrayRecursive(),
                                    'resize_keyboard' => $dto->resizeKeyboard ?? true,
                                ]
                                : []
                            ),
                        ]),
                    ]
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


    protected function setMyCommands(TelegramBotOutSetMyCommandsDto $dto): TelegramBotOutResponseDto
    {
        $options = $this->buildCommandOptions($dto->scope?->type->value, $dto->scope?->chatId, $dto->scope?->userId, $dto->language?->value);
        $json = $this->telegramApiService->setMyCommands(
            botToken: $dto->token,
            commands: $dto->commands->toArrayRecursive(),
            options: $options,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function unsetMyCommands(TelegramBotOutUnsetMyCommandsDto $dto): TelegramBotOutResponseDto
    {
        $options = $this->buildCommandOptions($dto->scope?->type->value, $dto->scope?->chatId, $dto->scope?->userId, $dto->language?->value);
        $json = $this->telegramApiService->unsetMyCommands(
            botToken: $dto->token,
            options: $options,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function getMyCommands(TelegramBotOutGetMyCommandsDto $dto): TelegramBotOutResponseDto
    {
        $options = $this->buildCommandOptions($dto->scope?->type->value, $dto->scope?->chatId, $dto->scope?->userId, $dto->language?->value);
        $json = $this->telegramApiService->getMyCommands(
            botToken: $dto->token,
            options: $options,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function forwardMessage(TelegramBotOutForwardMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->forwardMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            fromChatId: $dto->fromChatId,
            messageId: $dto->messageId,
            options: array_filter([
                'disable_notification' => $dto->disableNotification,
            ], static fn ($v) => $v !== null),
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function copyMessage(TelegramBotOutCopyMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->copyMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            fromChatId: $dto->fromChatId,
            messageId: $dto->messageId,
            options: array_filter([
                'caption' => $dto->caption,
                'parse_mode' => $dto->parseMode,
            ], static fn ($v) => $v !== null),
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function deleteMessage(TelegramBotOutDeleteMessageDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->deleteMessage(
            botToken: $dto->token,
            chatId: $dto->chatId,
            messageId: $dto->messageId,
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function editMessageText(TelegramBotOutEditMessageTextDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->editMessageText(
            botToken: $dto->token,
            text: $dto->text,
            options: array_filter([
                'chat_id' => $dto->chatId,
                'message_id' => $dto->messageId,
                'inline_message_id' => $dto->inlineMessageId,
                'parse_mode' => $dto->parseMode,
                'reply_markup' => $dto->replyMarkup ? json_encode($dto->replyMarkup) : null,
            ], static fn ($v) => $v !== null),
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function sendPhoto(TelegramBotOutSendPhotoDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendPhoto(
            botToken: $dto->token,
            chatId: $dto->chatId,
            photo: $dto->photo,
            caption: $dto->caption,
            parseMode: $dto->parseMode ?? 'HTML',
            options: $dto->options ?? [],
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function sendVideo(TelegramBotOutSendVideoDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendVideo(
            botToken: $dto->token,
            chatId: $dto->chatId,
            video: $dto->video,
            caption: $dto->caption,
            parseMode: $dto->parseMode ?? 'HTML',
            options: $dto->options ?? [],
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    protected function sendAudio(TelegramBotOutSendAudioDto $dto): TelegramBotOutResponseDto
    {
        $json = $this->telegramApiService->sendAudio(
            botToken: $dto->token,
            chatId: $dto->chatId,
            audio: $dto->audio,
            caption: $dto->caption,
            parseMode: $dto->parseMode ?? 'HTML',
            options: $dto->options ?? [],
        );

        return TelegramBotOutResponseDto::create($dto, $json);
    }


    /**
     * Формирует массив options для методов команд бота Telegram.
     */
    private function buildCommandOptions(?string $scopeType, ?int $chatId, ?int $userId, ?string $language): array
    {
        $options = [];

        if ($scopeType) {
            $scope = ['type' => $scopeType];
            if (in_array($scopeType, ['chat', 'chat_administrators', 'chat_member'], true) && $chatId) {
                $scope['chat_id'] = $chatId;
            }
            if ($scopeType === 'chat_member' && $userId) {
                $scope['user_id'] = $userId;
            }
            $options['scope'] = $scope;
        }

        if ($language) {
            $options['language_code'] = $language;
        }

        return $options;
    }
}
