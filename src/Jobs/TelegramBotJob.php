<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService;
use Throwable;

/**
 * @internal
 * Задача отправки сообщений в бота телеграм через очередь
 */
class TelegramBotJob extends DefaultJob
{
    public bool $withQueueLog = false;
    public      $tries        = 5;
    public      $backoff      = 0;


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
        try {
            !isDebug() ?: logger()->debug('TelegramBotJob: start', [
                'uuid'       => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
                'job_id'     => method_exists($this->job, 'getJobId') ? $this->job->getJobId() : null,
                'queue'      => method_exists($this->job, 'getQueue') ? $this->job->getQueue() : null,
                'connection' => method_exists($this->job, 'getConnectionName') ? $this->job->getConnectionName() : null,
                'attempts'   => $this->attempts(),
                'tries'      => $this->tries,
                'dto'        => [
                    'class'          => $this->dto::class,
                    'slug'           => property_exists($this->dto, 'slug') ? $this->dto->slug : null,
                    'externalChatId' => property_exists($this->dto, 'externalChatId')
                        ? $this->dto->externalChatId
                        : null,
                ],
            ]);
        } catch (Throwable $exception) {
            !isDebug() ?: logger()->debug('TelegramBotJob: debug log failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        // Очищаем маркер ошибки от предыдущих попыток/внешних вызовов,
        // чтобы не уходить в повтор без реального падения текущей отправки.
        if (is_array($this->dto->meta)) {
            unset($this->dto->meta['exception']);
        }

        app(TelegramBotService::class)->send($this->dto);

        // TelegramBotService перехватывает исключения, поэтому стандартный retry очереди не срабатывает.
        // Если в meta есть данные об исключении — делаем повторную попытку сразу (без ожидания).
        if (($this->dto->meta['exception'] ?? null) !== null) {
            if ($this->attempts() >= $this->tries) {
                !isDebug() ?: logger()->debug('TelegramBotJob: fail', [
                    'uuid'     => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
                    'job_id'   => method_exists($this->job, 'getJobId') ? $this->job->getJobId() : null,
                    'attempts' => $this->attempts(),
                    'tries'    => $this->tries,
                ]);

                $this->fail(new LaravelHelperException('Не удалось отправить сообщение в Telegram после нескольких попыток'));

                return;
            }

            !isDebug() ?: logger()->debug('TelegramBotJob: release', [
                'uuid'     => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
                'job_id'   => method_exists($this->job, 'getJobId') ? $this->job->getJobId() : null,
                'attempts' => $this->attempts(),
                'delay'    => 0,
            ]);

            $this->release(0);

            return;
        }
    }
}
