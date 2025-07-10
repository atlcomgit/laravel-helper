<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\ProfilerLog;

/**
 * Репозиторий логирования профилирования методов класса
 */
class ProfilerLogRepository extends DefaultRepository
{
    public function __construct(private ?string $profilerLogClass = null)
    {
        $this->profilerLogClass ??= lhConfig(ConfigEnum::ProfilerLog, 'model') ?? ProfilerLog::class;
    }


    /**
     * Создает запись лога консольной команды
     *
     * @param ProfilerLogDto $dto
     * @return void
     */
    public function create(ProfilerLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ProfilerLog $this->profilerLogClass */
            $this->profilerLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога консольной команды
     *
     * @param ProfilerLogDto $dto
     * @return void
     */
    public function update(ProfilerLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ProfilerLog $this->profilerLogClass */
            $this->profilerLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update($dto->toArray());
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
            /** @var ProfilerLog $this->profilerLogClass */
            return $this->profilerLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
