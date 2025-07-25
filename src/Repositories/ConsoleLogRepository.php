<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий логирования консольных команд
 */
class ConsoleLogRepository extends DefaultRepository
{
    public function __construct(private ?string $consoleLogClass = null)
    {
        $this->consoleLogClass ??= Lh::config(ConfigEnum::ConsoleLog, 'model') ?? ConsoleLog::class;
    }


    /**
     * Создает запись лога консольной команды
     *
     * @param ConsoleLogDto $dto
     * @return void
     */
    public function create(ConsoleLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ConsoleLog $this->consoleLogClass */
            $this->consoleLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray());
        });
    }


    /**
     * Обновляет запись лога консольной команды
     *
     * @param ConsoleLogDto $dto
     * @return void
     */
    public function update(ConsoleLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var ConsoleLog $this->consoleLogClass */
            $this->consoleLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid)
                ->update(
                    $dto->includeArray(
                        is_null($dto->output)
                        ? []
                        : ['output' => DB::raw("COALESCE(output, '') || '{$dto->output}'")]
                    )
                        ->toArray(),
                );
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
            /** @var ConsoleLog $this->consoleLogClass */
            return $this->consoleLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete();
        });
    }
}
