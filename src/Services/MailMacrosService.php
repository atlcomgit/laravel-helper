<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Events\MailFailed;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
        $macro = function ($view, $data = [], $callback = null) {
            try {
                //?!? if withMailLog
                /** @var \Illuminate\Mail\Mailer $this */
                return $this->send($view, $data, $callback);
            } catch (Throwable $exception) {
                $dto = null;
                if ($view instanceof Mailable) {
                    $dto = MailLogDto::createByMailable($view);
                } else {
                    $dto = MailLogDto::create([
                        'uuid' => uuid(),
                        'info' => ['view' => $view],
                    ]);
                }

                MailFailed::dispatch($dto, $exception);

                throw $exception;
            }
        };

        Mail::macro('sendWithLog', $macro); //?!? send
    }
}
