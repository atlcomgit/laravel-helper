<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ViewLog;

/**
 * @internal
 * Репозиторий логирования query запросов
 */
class ViewLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var ViewLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::ViewLog, 'model') ?? ViewLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function create(ViewLogDto $dto): void
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
     * @param ViewLogDto $dto
     * @return void
     */
    public function update(ViewLogDto $dto): void
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
