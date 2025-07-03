<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\QueryLog;

/**
 * Репозиторий логирования query запросов
 */
class QueryLogRepository extends DefaultRepository
{
    public function __construct(private ?string $queryLogClass = null)
    {
        $this->queryLogClass ??= lhConfig(ConfigEnum::QueryLog, 'model') ?? QueryLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function create(QueryLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var QueryLog $this->queryLogClass */
            $this->queryLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function update(QueryLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var QueryLog $this->queryLogClass */
            $this->queryLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update($dto->toArray());
        });
    }


    /**
     * Удаляет записи логов query запросов старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        return $this->withoutTelescope(function () use ($days) {
            /** @var QueryLog $this->queryLogClass */
            return $this->queryLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
