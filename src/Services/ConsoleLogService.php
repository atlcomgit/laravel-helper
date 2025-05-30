<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Repositories\ConsoleLogRepository;

/**
 * Сервис логирования консольных команд
 */
class ConsoleLogService
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
        if (!config('laravel-helper.console_log.enabled')) {
            return 0;
        }

        return $this->consoleLogRepository->cleanup($days);
    }
}
