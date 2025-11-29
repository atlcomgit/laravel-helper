<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;
use Illuminate\Mail\Events\MessageSent;

/**
 * Слушатель события успешной отправки письма
 */
class MailMessageSentListener extends DefaultListener
{
    public function handle(MessageSent $event): void
    {
        if (!Lh::config(ConfigEnum::MailLog, 'enabled')) {
            return;
        }

        if ($event->message->getHeaders()->has('X-Helper-Mailer-Logged')) {
            return;
        }

        $uuidHeader = $event->message->getHeaders()->get('X-Mail-Log-Uuid');
        $uuid = $uuidHeader ? $uuidHeader->getBodyAsString() : null;

        $dto = MailLogDto::createByMailable($event->message);
        $dto->uuid = $uuid ?? $dto->uuid; // Use existing uuid if found

        app(MailLogService::class)->success($dto);
    }
}
