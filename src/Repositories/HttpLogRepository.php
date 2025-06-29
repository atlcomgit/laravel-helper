<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\HttpLogCreateDto;
use Atlcom\LaravelHelper\Dto\HttpLogFailedDto;
use Atlcom\LaravelHelper\Dto\HttpLogUpdateDto;
use Atlcom\LaravelHelper\Models\HttpLog;

/**
 * Репозиторий логирования http запросов
 */
class HttpLogRepository extends DefaultRepository
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
        $this->withoutTelescope(function () use ($dto) {
            /** @var HttpLog $this->httpLogClass */
            $this->httpLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога http запроса
     *
     * @param HttpLogUpdateDto|HttpLogFailedDto $dto
     * @return void
     */
    public function update(HttpLogUpdateDto|HttpLogFailedDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var HttpLog $this->httpLogClass */
            $this->httpLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update($dto->toArray());
        });
    }


    /**
     * Удаляет записи логов http запросов старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        return $this->withoutTelescope(function () use ($days) {
            /** @var HttpLog $this->httpLogClass */
            return $this->httpLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
