<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Models\ViewLog;

/**
 * Репозиторий логирования query запросов
 */
class ViewLogRepository
{
    public function __construct(private ?string $viewLogClass = null)
    {
        $this->viewLogClass ??= config('laravel-helper.view_log.model') ?? ViewLog::class;
    }


    /**
     * Создает запись лога query запроса
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function create(ViewLogDto $dto): void
    {
        /** @var ViewLog $this->viewLogClass */
        $this->viewLogClass::query()->create($dto->toArray());
    }


    /**
     * Обновляет запись лога query запроса
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function update(ViewLogDto $dto): void
    {
        /** @var ViewLog $this->viewLogClass */
        $this->viewLogClass::query()
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
        /** @var ViewLog $this->viewLogClass */
        return $this->viewLogClass::query()
            ->whereDate('created_at', '<=', now()->subDays($days))
            ->delete();
    }
}
