<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Repositories\QueryLogRepository;

/**
 * Сервис логирования query запросов
 */
class QueryLogService
{
    public function __construct(
        private QueryLogRepository $queryLogRepository,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Сохраняет запись query запроса
     *
     * @param QueryLogDto $dto
     * @return void
     */
    public function log(QueryLogDto $dto): void
    {
        $dto->isUpdated
            ? $this->queryLogRepository->update($dto)
            : $this->queryLogRepository->create($dto);
    }


    /**
     * Очищает логи query запросов
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!config('laravel-helper.query_log.enabled')) {
            return 0;
        }

        return $this->queryLogRepository->cleanup($days);
    }
}
