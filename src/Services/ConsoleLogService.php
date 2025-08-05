<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Repositories\ConsoleLogRepository;

/**
 * @internal
 * Сервис логирования консольных команд
 */
class ConsoleLogService extends DefaultService
{
    public function __construct(private ConsoleLogRepository $consoleLogRepository) {}


    /**
     * Сохраняет запись лога консольной команды
     *
     * @param ConsoleLogDto $dto
     * @return void
     */
    public function log(ConsoleLogDto $dto): void
    {
        $dto->isUpdated
            ? $this->consoleLogRepository->update($dto)
            : $this->consoleLogRepository->create($dto);
    }


    /**
     * Очищает логи консольных команд
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!Lh::config(ConfigEnum::ConsoleLog, 'enabled')) {
            return 0;
        }

        return $this->consoleLogRepository->cleanup($days);
    }
}
