<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\QueueLog;

/**
 * Репозиторий логирования очередей
 */
class QueueLogRepository extends DefaultRepository
{
    public function __construct(private ?string $queueLogClass = null)
    {
        $this->queueLogClass ??= lhConfig(ConfigEnum::QueueLog, 'model') ?? QueueLog::class;
    }


    /**
     * Создает запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function create(QueueLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var QueueLog $this->queueLogClass */
            $this->queueLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function update(QueueLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var QueueLog $this->queueLogClass */
            $this->queueLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update($dto->toArray());
        });
    }


    /**
     * Удаляет записи логов очередей старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        return $this->withoutTelescope(function () use ($days) {
            /** @var QueueLog $this->queueLogClass */
            return $this->queueLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
