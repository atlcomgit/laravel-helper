<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ModelLog;
use Carbon\Carbon;
use DB;

/**
 * @internal
 * Репозиторий логирования моделей
 */
class ModelLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var ModelLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::ModelLog, 'model') ?? ModelLog::class;
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
            $this->model = Lh::config(ConfigEnum::ModelLog, 'model') ?? ModelLog::class;

            if ($dto->modelType !== $this->model) {
                $config = ConfigEnum::ModelLog;
                $connection = Lh::getConnection($config);
                $table = Lh::getTable($config);
                $data = $dto->serializeKeys(['type'])->toArray();
                $data['created_at'] = $data['created_at'] instanceof Carbon
                    ? $data['created_at']->toDateTimeString()
                    : (string)$data['created_at'];

                $columns = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $columnsSql = implode(', ', array_map(static fn (string $c): string => "`$c`", $columns));

                DB::connection($connection)
                    ->statement(
                        "insert into {$table} ({$columnsSql}) values ({$placeholders})",
                        array_values($data),
                    );
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
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->whereDate('created_at', '<=', now()->subDays($days))
                ->delete()
        );
    }
}
