<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\ModelLog;

/**
 * Репозиторий логирования моделей
 */
class ModelLogRepository extends DefaultRepository
{
    public function __construct(private ?string $modelLogClass = null)
    {
        $this->modelLogClass ??= lhConfig(ConfigEnum::ModelLog, 'model') ?? ModelLog::class;
    }


    /**
     * Создает запись лога модели
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function create(ModelLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ModelLog $this->modelLogClass */
            $this->modelLogClass = lhConfig(ConfigEnum::ModelLog, 'model') ?? ModelLog::class;

            if ($dto->modelType !== $this->modelLogClass) {
                $this->modelLogClass::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->create($dto->toArray());
            }
        });
    }


    /**
     * Удаляет записи логов консольных команд старше указанного количества дней
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        return $this->withoutTelescope(function () use ($days) {
            /** @var ModelLog $this->consoleLogClass */
            return $this->modelLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
