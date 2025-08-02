<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutResponseDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\TelegramBotJob;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotDto;

/**
 * Dto бота telegram
 */
class TelegramBotOutDto extends TelegramBotDto
{
    public string $token;
    public string $parseMode;
    public ?TelegramBotOutResponseDto $response;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'token' => (string)Lh::config(ConfigEnum::TelegramBot, 'token'),
            'parseMode' => 'HTML',
            'response' => null,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onCreated(mixed $data): void
    {
        parent::onCreated($data);

        $this->token
            ?: throw new LaravelHelperException('TelegramBot отключен');
    }


    /**
     * Отправляет dto в очередь для отправки сообщения в бота телеграм
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::TelegramBot, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? TelegramBotJob::dispatchSync($this)
                : TelegramBotJob::dispatch($this);
        }

        return $this;
    }
}
