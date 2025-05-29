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
    /**
     * Создает запись лога модели
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function create(ModelLogDto $dto): void
    {
        /** @var ModelLog $modelLogClass */
        $modelLogClass = config('laravel-helper.model_log.model') ?? ModelLog::class;

        if ($dto->modelType !== $modelLogClass && class_exists($modelLogClass)) {
            $modelLogClass::queryFrom(
                connection: config('laravel-helper.model_log.connection'),
                table: config('laravel-helper.model_log.table'),
            )
                ->create($dto->toArray());
        }
    }
}
