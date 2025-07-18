<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ViewLog;

/**
 * Репозиторий логирования query запросов
 */
class ViewLogRepository extends DefaultRepository
{
    public function __construct(private ?string $viewLogClass = null)
    {
        $this->viewLogClass ??= Lh::config(ConfigEnum::ViewLog, 'model') ?? ViewLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function create(ViewLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ViewLog $this->viewLogClass */
            $this->viewLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога query запроса
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function update(ViewLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ViewLog $this->viewLogClass */
            $this->viewLogClass::query()
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
            /** @var ViewLog $this->viewLogClass */
            return $this->viewLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
