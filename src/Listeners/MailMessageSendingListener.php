<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;
use Illuminate\Mail\Events\MessageSending;

/**
 * Слушатель события отправки письма
 */
class MailMessageSendingListener extends DefaultListener
{
    public function handle(MessageSending $event): void
    {
        if (!Lh::config(ConfigEnum::MailLog, 'enabled')) {
            return;
        }

        if ($event->message->getHeaders()->has('X-Helper-Mailer-Logged')) {
            return;
        }

        if (!Lh::config(ConfigEnum::MailLog, 'global')) {
            return;
        }

        $dto = MailLogDto::createByMailable($event->message); // event->message is Email (Symfony)

        // Store uuid in message headers to retrieve it in MessageSent
        $event->message->getHeaders()->addTextHeader('X-Mail-Log-Uuid', $dto->uuid);

        if (Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
            app(MailLogService::class)->create($dto);
        }
    }
}
