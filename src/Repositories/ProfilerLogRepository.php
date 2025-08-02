<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ProfilerLog;

/**
 * Репозиторий логирования профилирования методов класса
 */
class ProfilerLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var ProfilerLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::ProfilerLog, 'model') ?? ProfilerLog::class;
    }


    /**
     * Создает запись лога консольной команды
     *
     * @param ProfilerLogDto $dto
     * @return void
     */
    public function create(ProfilerLogDto $dto): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray())
        );
    }


    /**
     * Обновляет запись лога консольной команды
     *
     * @param ProfilerLogDto $dto
     * @return void
     */
    public function update(ProfilerLogDto $dto): void
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
     * Удаляет записи логов консольных команд старше указанного количества дней
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
