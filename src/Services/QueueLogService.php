<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Repositories\QueueLogRepository;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Throwable;

/**
 * @internal
 * Сервис логирования очередей
 */
class QueueLogService extends DefaultService
{
    public function __construct(
        private QueueLogRepository $queueLogRepository,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Ставит логирование очереди в очередь
     *
     * @param JobProcessing|JobProcessed|JobFailed $event
     * @return void
     */
    public function job(JobProcessing|JobProcessed|JobFailed $event): void
    {
        $name = $event->job->resolveName();

        if (
            !Lh::config(ConfigEnum::QueueLog, 'enabled')
            || (($event instanceof JobProcessing) && !Lh::config(ConfigEnum::QueueLog, 'store_on_start'))
            || ($name === QueueLogJob::class)
        ) {
            return;
        }

        $uuid = $event->job->uuid();
        $payload = json_decode($event->job->getRawBody(), true);
        !($event instanceof JobProcessing) ?: ApplicationDto::create(
            uuid: $uuid,
            type: ApplicationTypeEnum::Queue,
            class: $event->job::class,
        );

        // Важно: логирование очереди НЕ должно ломать выполнение job.
        // Десериализация команды может падать (например, при смене кода/версий), поэтому делаем её безопасной.
        $commandRaw = $payload['data']['command'] ?? null;
        $command = null;
        $unserializeError = null;

        if (is_string($commandRaw) && $commandRaw !== '') {
            try {
                $command = @unserialize($commandRaw);

                if ($command === false && $commandRaw !== 'b:0;') {
                    $command = null;
                }
            } catch (Throwable $exception) {
                $command = null;
                $unserializeError = $exception->getMessage();
            }
        }

        $payload['data']['command'] = (is_object($command) && method_exists($command, 'toArray'))
            ? $command->toArray()
            : json_decode(json_encode($command, Hlp::jsonFlags()), true);

        !$unserializeError ?: $payload['data']['command_unserialize_error'] = $unserializeError;

        $dto = QueueLogDto::create(
            uuid: $uuid,
            jobId: $event->job->getJobId(),
            jobName: Hlp::pathClassName($event->job::class),
            name: Hlp::pathClassName($name),
            connection: $event->job->getConnectionName(),
            queue: $event->job->getQueue(),
            payload: $payload, // $event->job->getRawBody(),
            attempts: $event->job->attempts(),
            exception: $event instanceof JobFailed ? Hlp::exceptionToString($event->exception) : null,
            withQueueLog: is_array($command) && ($command['withQueueLog'] ?? false),
            isUpdated: ($event instanceof JobProcessed || $event instanceof JobFailed)
            && Lh::config(ConfigEnum::QueueLog, 'store_on_start'),
            status: match (true) {
                $event instanceof JobProcessing => QueueLogStatusEnum::Process,
                $event instanceof JobProcessed => QueueLogStatusEnum::Success,
                $event instanceof JobFailed => QueueLogStatusEnum::Failed,
            },
        );

        !($dto->isUpdated || !Lh::config(ConfigEnum::QueueLog, 'store_on_start'))
            ?: $dto->merge([
                'duration' => $duration = Carbon::parse($payload['createdAt'] ?? '')->diffInMilliseconds() / 1000,
                'memory'   => $memory = ApplicationDto::restore()?->getMemory(),
                'info'     => [
                    'class'    => $name,
                    'duration' => Hlp::timeSecondsToString(value: $duration, withMilliseconds: true),
                    'memory'   => Hlp::sizeBytesToString($memory),
                    'deleted'  => $event->job->isDeleted(),
                    'released' => $event->job->isReleased(),
                    'failed'   => $event->job->hasFailed(),
                ],
            ]);

        $dto->dispatch();
    }


    /**
     * Сохраняет запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function log(QueueLogDto $dto): void
    {
        $dto->isUpdated
            ? $this->queueLogRepository->update($dto)
            : $this->queueLogRepository->create($dto);
    }


    /**
     * Очищает логи очередей
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!Lh::config(ConfigEnum::QueueLog, 'enabled')) {
            return 0;
        }

        return $this->queueLogRepository->cleanup($days);
    }
}
