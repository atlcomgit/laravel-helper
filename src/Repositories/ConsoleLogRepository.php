<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 * Репозиторий логирования консольных команд
 */
class ConsoleLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var ConsoleLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::ConsoleLog, 'model') ?? ConsoleLog::class;
    }


    /**
     * Создает запись лога консольной команды
     *
     * @param ConsoleLogDto $dto
     * @return void
     */
    public function create(ConsoleLogDto $dto): void
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
     * @param ConsoleLogDto $dto
     * @return void
     */
    public function update(ConsoleLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            $query = $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofUuid($dto->uuid);

            $query->update(
                $dto->includeArray(
                    is_null($dto->output)
                    ? []
                    : [
                        'output' => DB::raw($this->outputConcatSql(
                            $query->getModel()->getConnection(),
                            $dto->output,
                        )),
                    ]
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
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete()
        );
    }


    /**
     * Возвращает безопасное выражение конкатенации вывода команды
     *
     * @param Connection $connection
     * @param string $output
     * @return string
     */
    private function outputConcatSql(Connection $connection, string $output): string
    {
        $safeOutput = (string)Hlp::sqlSafeValue($output);
        $quotedOutput = $connection->getPdo()->quote($safeOutput);

        if ($quotedOutput === false) {
            $quotedOutput = "''";
        }

        return match ($connection->getDriverName()) {
            'pgsql' => "COALESCE(output, '') || {$quotedOutput}",
            default => "CONCAT(COALESCE(output, ''), {$quotedOutput})",
        };
    }
}
