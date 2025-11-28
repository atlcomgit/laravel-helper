<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\MailFailed;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;

/**
 * Слушатель события ошибки отправки письма
 */
class MailMessageFailedListener extends DefaultListener
{
    public function handle(MailFailed $event): void
    {
        if (!Lh::config(ConfigEnum::MailLog, 'enabled')) {
            return;
        }

        $dto = $event->dto;
        $dto->exception = $event->exception;
        //?!? delete
        $dto->error_message = $event->exception->getMessage();

        app(MailLogService::class)->failed($dto);
    }
}
