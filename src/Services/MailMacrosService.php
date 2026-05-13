<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Illuminate\Support\Facades\Mail;

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
        Mail::macro('withLog', function (?bool $enabled = null) {
            /** @var \Illuminate\Mail\MailManager|\Illuminate\Mail\Mailer $this */
            $mailer = method_exists($this, 'mailer') ? $this->mailer() : $this;

            return $mailer->withLog($enabled);
        });

        Mail::macro('withMailLog', function (?bool $enabled = null) {
            /** @var \Illuminate\Mail\MailManager|\Illuminate\Mail\Mailer $this */
            $mailer = method_exists($this, 'mailer') ? $this->mailer() : $this;

            return $mailer->withMailLog($enabled);
        });

        Mail::macro('sendWithLog', function ($view, array $data = [], $callback = null) {
            /** @var \Illuminate\Mail\MailManager|\Illuminate\Mail\Mailer $this */
            $mailer = method_exists($this, 'mailer') ? $this->mailer() : $this;

            return $mailer->withLog(true)->send($view, $data, $callback);
        });

        Mail::macro('sendNowWithLog', function ($view, array $data = [], $callback = null) {
            /** @var \Illuminate\Mail\MailManager|\Illuminate\Mail\Mailer $this */
            $mailer = method_exists($this, 'mailer') ? $this->mailer() : $this;

            return $mailer->withLog(true)->sendNow($view, $data, $callback);
        });
    }
}
