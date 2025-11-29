<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Mail;

use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailer;
use Throwable;

/**
 * Расширенный класс Mailer с поддержкой логирования
 */
class HelperMailer extends Mailer
{
    /**
     * Флаг включения логирования для текущей отправки
     *
     * @var bool|null
     */
    protected ?bool $withMailLog = null;

    /**
     * Устанавливает флаг логирования для следующей отправки
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function withLog(?bool $enabled = null): self
    {
        $this->withMailLog = $enabled ?? true;

        return $this;
    }


    /**
     * Устанавливает флаг логирования для следующей отправки (alias)
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function withMailLog(?bool $enabled = null): self
    {
        return $this->withLog($enabled);
    }


    /**
     * Определяет, нужно ли логировать отправку
     *
     * @return bool
     */
    protected function shouldLog(): bool
    {
        // Если логирование выключено глобально в конфиге
        if (!Lh::config(ConfigEnum::MailLog, 'enabled')) {
            return false;
        }

        // Если задан локальный флаг, используем его
        if ($this->withMailLog !== null) {
            return $this->withMailLog;
        }

        // Иначе используем глобальную настройку
        return Lh::config(ConfigEnum::MailLog, 'global');
    }


    /**
     * Флаг обработки Mailable, чтобы избежать дублирования логов
     *
     * @var bool
     */
    protected bool $processingMailable = false;

    /**
     * Send a new message using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($view, array $data = [], $callback = null)
    {
        // Если уже обрабатываем Mailable (вызвано из sendNow или send с Mailable),
        // то просто вызываем родительский метод, чтобы не дублировать лог
        if ($this->processingMailable) {
            return parent::send($view, $data, $callback);
        }

        if (!$this->shouldLog()) {
            return parent::send($view, $data, $callback);
        }

        $dto = MailLogDto::createFromPendingMail($view, $data);

        // Если передали Mailable, ставим флаг
        if ($view instanceof MailableContract) {
            $this->processingMailable = true;

            if (method_exists($view, 'withSymfonyMessage')) {
                $view->withSymfonyMessage(function ($message) use ($dto) {
                    $message->getHeaders()->addTextHeader('X-Helper-Mailer-Logged', 'true');
                    $message->getHeaders()->addTextHeader('X-Mail-Log-Uuid', $dto->uuid);
                });
            }
        }

        $originalCallback = $callback;
        $callback = function ($message) use ($originalCallback, $dto) {
            $message->getHeaders()->addTextHeader('X-Helper-Mailer-Logged', 'true');
            $message->getHeaders()->addTextHeader('X-Mail-Log-Uuid', $dto->uuid);

            if ($originalCallback) {
                $originalCallback($message);
            }
        };

        // Если включено сохранение перед отправкой
        if (Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
            app(MailLogService::class)->create($dto);
        }

        try {
            $result = parent::send($view, $data, $callback);
            $dto->update($result);

            if ($result) {
                $dto->updateFromEmail($result->getOriginalMessage());
            }

            app(MailLogService::class)->success($dto);
            return $result;

        } catch (Throwable $exception) {
            $dto->message = $exception->getMessage();
            $dto->exception = $exception;
            $dto->update();

            if ($view instanceof MailableContract) {
                $dto->updateFromMailable($view);
            }

            app(MailLogService::class)->failed($dto);

            throw $exception;

        } finally {
            if ($view instanceof MailableContract) {
                $this->processingMailable = false;
            }

            // Сбрасываем флаг после отправки
            $this->withMailLog = null;
        }
    }


    /**
     * Send a new message synchronously using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $mailable
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function sendNow($mailable, array $data = [], $callback = null)
    {
        // Если это Mailable, то логируем здесь
        if ($mailable instanceof MailableContract) {
            if ($this->processingMailable) {
                return parent::sendNow($mailable, $data, $callback);
            }

            if (!$this->shouldLog()) {
                return parent::sendNow($mailable, $data, $callback);
            }

            $this->processingMailable = true;

            $dto = MailLogDto::createFromPendingMail($mailable, $data);

            if (method_exists($mailable, 'withSymfonyMessage')) {
                $mailable->withSymfonyMessage(function ($message) use ($dto) {
                    $message->getHeaders()->addTextHeader('X-Helper-Mailer-Logged', 'true');
                    $message->getHeaders()->addTextHeader('X-Mail-Log-Uuid', $dto->uuid);
                });
            }

            if (Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
                app(MailLogService::class)->create($dto);
            }

            try {
                $result = parent::sendNow($mailable, $data, $callback);
                $dto->update($result);

                if ($result) {
                    $dto->updateFromEmail($result->getOriginalMessage());
                }

                app(MailLogService::class)->success($dto);

                return $result;

            } catch (Throwable $exception) {
                $dto->message = $exception->getMessage();
                $dto->exception = $exception;
                $dto->update();

                if ($mailable instanceof MailableContract) {
                    $dto->updateFromMailable($mailable);
                }

                app(MailLogService::class)->failed($dto);

                throw $exception;

            } finally {
                $this->processingMailable = false;
                $this->withMailLog = null;
            }
        }

        // Если это не Mailable (строка view), то просто вызываем parent::sendNow.
        // Он вызовет $this->send(), который и залогирует отправку.
        return parent::sendNow($mailable, $data, $callback);
    }
}
