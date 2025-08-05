<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\HttpLogCreateDto;
use Atlcom\LaravelHelper\Dto\HttpLogFailedDto;
use Atlcom\LaravelHelper\Dto\HttpLogUpdateDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\HttpLog;

/**
 * @internal
 * Репозиторий логирования http запросов
 */
class HttpLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var HttpLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::HttpLog, 'model') ?? HttpLog::class;
    }


    /**
     * Создает запись лога http запроса
     *
     * @param HttpLogCreateDto $dto
     * @return void
     */
    public function create(HttpLogCreateDto $dto): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray())
        );
    }


    /**
     * Обновляет запись лога http запроса
     *
     * @param HttpLogUpdateDto|HttpLogFailedDto $dto
     * @return void
     */
    public function update(HttpLogUpdateDto|HttpLogFailedDto $dto): void
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
     * Удаляет записи логов http запросов старше указанного количества дней
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
