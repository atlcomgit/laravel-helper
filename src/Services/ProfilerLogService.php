<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ProfilerLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Repositories\ProfilerLogRepository;

/**
 * Сервис логирования профилирования методов класса
 */
class ProfilerLogService extends DefaultService
{
    public function __construct(private ProfilerLogRepository $profilerLogRepository) {}


    /**
     * Сохраняет запись лога консольной команды
     *
     * @param ProfilerLogDto $dto
     * @return void
     */
    public function log(ProfilerLogDto $dto): void
    {
        $dto->isUpdated
            ? $this->profilerLogRepository->update($dto)
            : $this->profilerLogRepository->create($dto);
    }


    /**
     * Очищает логи консольных команд
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!lhConfig(ConfigEnum::ProfilerLog, 'enabled')) {
            return 0;
        }

        return $this->profilerLogRepository->cleanup($days);
    }
}
