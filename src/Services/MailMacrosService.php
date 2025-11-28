<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Listeners\MailMessageSendingListener;
use Atlcom\LaravelHelper\Listeners\MailMessageSentListener;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * Сервис регистрации mail макросов (и слушателей)
 */
class MailMacrosService extends DefaultService
{
    /**
     * Добавляет макросы и слушатели для логирования писем
     *
     * @return void
     */
    public static function setMacros(): void
    {
        // PendingMail is not macroable in all versions, so we use events for logging.
        // This covers Mail::to()->send() and Mail::send().

        Event::listen(MessageSending::class, MailMessageSendingListener::class);
        Event::listen(MessageSent::class, MailMessageSentListener::class);
        Event::listen(MessageFailed::class, MailMessageFailedListener::class);

        // Note: Failed emails (exceptions) are not caught by standard events.
        // To catch them, we would need to wrap the sender, which is not easily possible via macros on PendingMail.
        // Users should handle exceptions in their code or use a global handler.
    }
}
