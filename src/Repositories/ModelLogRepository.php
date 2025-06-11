<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Models\ModelLog;

/**
 * Репозиторий логирования моделей
 */
class ModelLogRepository
{
    public function __construct(private ?string $modelLogClass = null)
    {
        $this->modelLogClass ??= config('laravel-helper.model_log.model') ?? ModelLog::class;
    }


    /**
     * Создает запись лога модели
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function create(ModelLogDto $dto): void
    {
        /** @var ModelLog $this->modelLogClass */
        $this->modelLogClass = config('laravel-helper.model_log.model') ?? ModelLog::class;

        if ($dto->modelType !== $this->modelLogClass) {
            $this->modelLogClass::queryFrom(
                connection: config('laravel-helper.model_log.connection'),
                table: config('laravel-helper.model_log.table'),
            )
                ->create($dto->toArray());
        }
    }


    /**
     * Удаляет записи логов консольных команд старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        /** @var ModelLog $this->consoleLogClass */
        return $this->modelLogClass::queryFrom(
            connection: config('laravel-helper.model_log.connection'),
            table: config('laravel-helper.model_log.table'),
        )
            ->whereDate('created_at', '<=', now()->subDays($days))
            ->delete();
    }
}
