<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Repositories\QueueLogRepository;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

/**
 * Сервис логирования очередей
 */
class QueueLogService
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
            !config('laravel-helper.queue_log.enabled')
            || (($event instanceof JobProcessing) && !config('laravel-helper.queue_log.store_on_start'))
            || ($name === QueueLogJob::class)
        ) {
            return;
        }

        $uuid = $event->job->uuid();
        $memoryCacheKey = "job_memory_{$uuid}";
        !($event instanceof JobProcessing) ?: Hlp::cacheRuntimeSet($memoryCacheKey, memory_get_usage());
        $payload = json_decode($event->job->getRawBody(), true);

        $command = unserialize($payload['data']['command'] ?? '');
        $command = (is_object($command) && method_exists($command, 'toArray'))
            ? $command->toArray()
            : json_decode(json_encode($command, Hlp::jsonFlags()), true);
        $payload['data']['command'] = $command;

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
            withJobLog: is_array($command) && ($command['withJobLog'] ?? false),
            isUpdated: ($event instanceof JobProcessed || $event instanceof JobFailed)
            && config('laravel-helper.queue_log.store_on_start'),
            status: match (true) {
                $event instanceof JobProcessing => QueueLogStatusEnum::Process,
                $event instanceof JobProcessed => QueueLogStatusEnum::Success,
                $event instanceof JobFailed => QueueLogStatusEnum::Failed,
            },
        );

        !($dto->isUpdated || !config('laravel-helper.queue_log.store_on_start'))
            ?: $dto->merge([
                'info' => [
                    'class' => $name,
                    'duration' => Hlp::timeSecondsToString(
                        value: Carbon::parse($payload['createdAt'] ?? '')->diffInMilliseconds() / 1000,
                        withMilliseconds: true,
                    ),
                    'memory' => Hlp::sizeBytesToString(
                        memory_get_usage() - Hlp::cacheRuntimeGet($memoryCacheKey, memory_get_usage())
                    ),
                    'deleted' => $event->job->isDeleted(),
                    'released' => $event->job->isReleased(),
                    'failed' => $event->job->hasFailed(),
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
        if (!config('laravel-helper.queue_log.enabled')) {
            return 0;
        }

        return $this->queueLogRepository->cleanup($days);
    }
}
