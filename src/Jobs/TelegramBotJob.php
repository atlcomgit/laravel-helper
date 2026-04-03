<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService;

/**
 * @internal
 * Задача отправки сообщений в бота телеграм через очередь
 */
class TelegramBotJob extends DefaultJob
{
    public bool $withQueueLog = false;
    public      $tries        = 5;
    public      $backoff      = 0;
    public int  $timeout      = 120;


    public function __construct(protected TelegramBotOutDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::TelegramBot, 'queue'));

        $httpTimeout = max(1, (int)Lh::config(ConfigEnum::Http, 'telegramOrg.timeout'));
        $retryEnabled = (bool)Lh::config(ConfigEnum::Http, 'telegramOrg.retry.enabled');
        $retryTimes = $retryEnabled ? max(1, (int)Lh::config(ConfigEnum::Http, 'telegramOrg.retry.times')) : 1;
        $retrySleep = max(0, (int)Lh::config(ConfigEnum::Http, 'telegramOrg.retry.sleep'));
        $retryBudget = $httpTimeout * $retryTimes;
        $sleepBudget = (int)ceil(($retrySleep * max(0, $retryTimes - 1)) / 1000);

        // Закладываем запас поверх штатных HTTP retry Telegram API,
        // чтобы worker не обрывал задачу раньше времени.
        $this->timeout = max(120, $retryBudget + $sleepBudget + 15);
    }


    /**
     * Обработка задачи логирования задач
     *
     * @return void
     */
    public function __invoke()
    {
        $dispatchSync = (
            (Lh::config(ConfigEnum::TelegramBot, 'queue_dispatch_sync') ?? (isLocal() || isDev() || isTesting()))
            || $this->dto->useSendSync
        );

        // Очищаем маркер ошибки от предыдущих попыток/внешних вызовов,
        // чтобы не уходить в повтор без реального падения текущей отправки.
        if (is_array($this->dto->meta)) {
            unset($this->dto->meta['exception']);
        }

        app(TelegramBotService::class)->send($this->dto);

        // TelegramBotService перехватывает исключения, поэтому стандартный retry очереди не срабатывает.
        // Если в meta есть данные об исключении — делаем повторную попытку сразу (без ожидания).
        if (($this->dto->meta['exception'] ?? null) !== null) {
            // Важно: при Redis release(0) попадает в delayed zset, а при block_for=60 воркер
            // может подобрать задачу только через ~минуту. Поэтому делаем немедленный re-dispatch.
            $retryAttempt = is_array($this->dto->meta)
                ? (int)($this->dto->meta['queue_retry_attempt'] ?? 0)
                : 0;
            $retryAttempt++;
            $maxRetryAttempts = $dispatchSync ? 1 : (int)$this->tries;

            $this->dto->meta = [
                ...(is_array($this->dto->meta) ? $this->dto->meta : []),
                'queue_retry_attempt' => $retryAttempt,
            ];

            // Для sync-dispatch дополнительный self-dispatch только раздувает общее время
            // выполнения вложенной отправки. В этом режиме полагаемся на HTTP retry,
            // а доменный retry уже делает приложение через статус рассылки.
            if ($retryAttempt >= $maxRetryAttempts) {
                $this->fail(new LaravelHelperException('Не удалось отправить сообщение в Telegram после исчерпания доступных попыток'));

                return;
            }

            // Делаем dispatch задачи в ready-очередь, чтобы worker подхватил её мгновенно
            $dispatchSync
                ? self::dispatchSync($this->dto)
                : self::dispatch($this->dto)->onQueue(Lh::config(ConfigEnum::TelegramBot, 'queue'));

            $this->delete();

            return;
        }
    }
}
