<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\MailFailed;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * @internal
 * Сервис регистрации mail макросов (и слушателей)
 */
//?!? 
class MailMacrosService extends DefaultService
{
    /**
     * Добавляет макросы и слушатели для логирования писем
     *
     * @return void
     */
    public static function setMacros(): void
    {
        // if (Lh::config(ConfigEnum::MailLog, 'enabled')) {
        //     // Регистрация макроса логирования mail отправки
        //     $withMailLogMacro = function (?bool $enabled = null) {
        //         /** @var \Illuminate\Mail\Mailer $this */
        //         return Lh::config(ConfigEnum::Macros, 'mail.enabled')
        //             && Lh::config(ConfigEnum::MailLog, 'enabled')
        //             ? app(MailLogService::class)->setMacro($this, $enabled)
        //             : $this;
        //     };

        //     Mail::macro('withLog', $withMailLogMacro);
        //     Mail::macro('withMailLog', $withMailLogMacro);
        // }


        // $sendMacro = function ($view, $data = [], $callback = null) {
        //     try {
        //         /** @var \Illuminate\Mail\Mailer $this */
        //         return $this->send($view, $data, $callback);
        //     } catch (Throwable $exception) {
        //         MailFailed::dispatch(MailLogDto::createFromPendingMail($view, $data), $exception);

        //         throw $exception;
        //     }
        // };

        // $sendNowMacro = function ($view, $data = [], $callback = null) {
        //     try {
        //         /** @var \Illuminate\Mail\Mailer $this */
        //         return $this->sendNow($view, $data, $callback);
        //     } catch (Throwable $exception) {
        //         MailFailed::dispatch(MailLogDto::createFromPendingMail($view, $data), $exception);

        //         throw $exception;
        //     }
        // };

        // Mail::macro('send', $sendMacro);
        // Mail::macro('sendNow', $sendNowMacro);
    }
}