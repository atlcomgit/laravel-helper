<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\QueryLog;

/**
 * @internal
 * Репозиторий логирования query запросов
 */
class QueryLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var QueryLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::QueryLog, 'model') ?? QueryLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function create(QueryLogDto $dto): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray())
        );
    }


    /**
     * Обновляет запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function update(QueryLogDto $dto): void
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
     * Удаляет записи логов query запросов старше указанного количества дней
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
