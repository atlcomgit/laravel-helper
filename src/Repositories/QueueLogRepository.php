<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Models\QueueLog;

/**
 * Репозиторий логирования очередей
 */
class QueueLogRepository
{
    public function __construct(private ?string $queueLogClass = null)
    {
        $this->queueLogClass ??= config('laravel-helper.queue_log.model') ?? QueueLog::class;
    }


    /**
     * Создает запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function create(QueueLogDto $dto): void
    {
        /** @var QueueLog $this->queueLogClass */
        $this->queueLogClass::queryFrom(
            connection: config('laravel-helper.queue_log.connection'),
            table: config('laravel-helper.queue_log.table'),
        )
            ->create($dto->toArray());
    }


    /**
     * Обновляет запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function update(QueueLogDto $dto): void
    {
        /** @var QueueLog $this->queueLogClass */
        $this->queueLogClass::queryFrom(
            connection: config('laravel-helper.queue_log.connection'),
            table: config('laravel-helper.queue_log.table'),
        )
            ->ofUuid($dto->uuid)
            ->update($dto->toArray());
    }


    /**
     * Удаляет записи логов очередей старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        /** @var QueueLog $this->queueLogClass */
        return $this->queueLogClass::queryFrom(
            connection: config('laravel-helper.queue_log.connection'),
            table: config('laravel-helper.queue_log.table'),
        )
            ->whereDate('created_at', '<=', now()->subDays($days))
            ->delete();
    }
}
