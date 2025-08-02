<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\QueueLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\QueueLog;

/**
 * Репозиторий логирования очередей
 */
class QueueLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var QueueLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::QueueLog, 'model') ?? QueueLog::class;
    }


    /**
     * Создает запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function create(QueueLogDto $dto): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray())
        );
    }


    /**
     * Обновляет запись лога очереди
     *
     * @param QueueLogDto $dto
     * @return void
     */
    public function update(QueueLogDto $dto): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update($dto->toArray())
        );
    }


    /**
     * Удаляет записи логов очередей старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete()
        );
    }
}
