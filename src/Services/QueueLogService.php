<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Repositories\QueueLogRepository;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

/**
 * Сервис логирования задач
 */
class QueueLogService
{
    public function __construct(
        private QueueLogRepository $queueLogRepository,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Ставит логирование задачи в очередь
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
        !($event instanceof JobProcessing) ?: Helper::cacheRuntimeSet($memoryCacheKey, memory_get_usage());
        $payload = json_decode($event->job->getRawBody(), true);

        $command = unserialize($payload['data']['command']);
        $command = (is_object($command) && method_exists($command, 'toArray'))
            ? $command->toArray()
            : json_decode(json_encode($command, Helper::jsonFlags()), true);
        $payload['data']['command'] = $command;

        $dto = QueueLogDto::create(
            uuid: $uuid,
            jobId: $event->job->getJobId(),
            jobName: Helper::pathClassName($event->job::class),
            name: Helper::pathClassName($name),
            connection: $event->job->getConnectionName(),
            queue: $event->job->getQueue(),
            payload: $payload, // $event->job->getRawBody(),
            attempts: $event->job->attempts(),
            exception: $event instanceof JobFailed ? Helper::exceptionToString($event->exception) : null,
            isUpdated: $event instanceof JobProcessed || $event instanceof JobFailed,
            status: match (true) {
                $event instanceof JobProcessing => QueueLogStatusEnum::Process,
                $event instanceof JobProcessed => QueueLogStatusEnum::Success,
                $event instanceof JobFailed => QueueLogStatusEnum::Failed,
            },
        );

        !$dto->isUpdated ?: $dto->merge([
            'info' => [
                'class' => $name,
                'duration' => Helper::timeSecondsToString(
                    (int)Carbon::parse($payload['createdAt'] ?? '')->diffInMilliseconds() / 1000
                ),
                'memory' => Helper::sizeBytesToString(
                    memory_get_usage() - Helper::cacheRuntimeGet($memoryCacheKey, memory_get_usage())
                ),
                'deleted' => $event->job->isDeleted(),
                'released' => $event->job->isReleased(),
                'failed' => $event->job->hasFailed(),
            ],
        ]);

        $dto->dispatch();
    }


    /**
     * Сохраняет запись лога задачи
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
     * Очищает логи задач
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
