<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\TelegramBotEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService;

/**
 * Задача отправки сообщений в бота телеграм через очередь
 */
class TelegramBotJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected TelegramBotOutDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::TelegramBot, 'queue'));
    }


    /**
     * Обработка задачи логирования задач
     *
     * @return void
     */
    public function __invoke()
    {
        app(TelegramBotService::class)->send($this->dto);

        event(new TelegramBotEvent($this->dto));
    }
}
