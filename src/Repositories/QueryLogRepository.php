<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Models\QueryLog;

/**
 * Репозиторий логирования query запросов
 */
class QueryLogRepository
{
    public function __construct(private ?string $queryLogClass = null)
    {
        $this->queryLogClass ??= config('laravel-helper.query_log.model') ?? QueryLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function create(QueryLogDto $dto): void
    {
        /** @var QueryLog $this->queryLogClass */
        $this->queryLogClass::queryFrom(
            connection: config('laravel-helper.query_log.connection'),
            table: config('laravel-helper.query_log.table'),
        )
            ->create($dto->toArray());
    }


    /**
     * Обновляет запись лога query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function update(QueryLogDto $dto): void
    {
        /** @var QueryLog $this->queryLogClass */
        $this->queryLogClass::queryFrom(
            connection: config('laravel-helper.query_log.connection'),
            table: config('laravel-helper.query_log.table'),
        )
            ->ofUuid($dto->uuid)
            ->update($dto->toArray());
    }


    /**
     * Удаляет записи логов query запросов старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        /** @var QueryLog $this->queryLogClass */
        return $this->queryLogClass::queryFrom(
            connection: config('laravel-helper.query_log.connection'),
            table: config('laravel-helper.query_log.table'),
        )
            ->whereDate('created_at', '<=', now()->subDays($days))
            ->delete();
    }
}
