<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Dto\HttpLogCreateDto;
use Atlcom\LaravelHelper\Dto\HttpLogFailedDto;
use Atlcom\LaravelHelper\Dto\HttpLogUpdateDto;
use Atlcom\LaravelHelper\Models\HttpLog;

/**
 * Репозиторий логирования http запросов
 */
class HttpLogRepository
{
    public function __construct(private ?string $httpLogClass = null)
    {
        $this->httpLogClass ??= config('laravel-helper.http_log.model') ?? HttpLog::class;
    }


    /**
     * Создает запись лога http запроса
     *
     * @param HttpLogCreateDto $dto
     * @return void
     */
    public function create(HttpLogCreateDto $dto): void
    {
        /** @var HttpLog $this->httpLogClass */
        $this->httpLogClass::queryFrom(
            connection: config('laravel-helper.http_log.connection'),
            table: config('laravel-helper.http_log.table'),
        )
            ->create($dto->toArray());
    }


    /**
     * Обновляет запись лога http запроса
     *
     * @param HttpLogUpdateDto|HttpLogFailedDto $dto
     * @return void
     */
    public function update(HttpLogUpdateDto|HttpLogFailedDto $dto): void
    {
        /** @var HttpLog $this->httpLogClass */
        $this->httpLogClass::queryFrom(
            connection: config('laravel-helper.http_log.connection'),
            table: config('laravel-helper.http_log.table'),
        )
            ->ofUuid($dto->uuid)
            ->update($dto->toArray());
    }


    /**
     * Удаляет записи логов http запросов старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        /** @var HttpLog $this->httpLogClass */
        return $this->httpLogClass::queryFrom(
            connection: config('laravel-helper.http_log.connection'),
            table: config('laravel-helper.http_log.table'),
        )
            ->whereDate('created_at', '<=', now()->subDays($days))
            ->delete();
    }
}
