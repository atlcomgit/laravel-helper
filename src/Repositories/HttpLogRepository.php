<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\HttpLogCreateDto;
use Atlcom\LaravelHelper\Dto\HttpLogFailedDto;
use Atlcom\LaravelHelper\Dto\HttpLogUpdateDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\HttpLog;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 * Репозиторий логирования http запросов
 */
class HttpLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var HttpLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::HttpLog, 'model') ?? HttpLog::class;
    }


    /**
     * Создает запись лога http запроса
     *
     * @param HttpLogCreateDto $dto
     * @return void
     */
    public function create(HttpLogCreateDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            $this->model = Lh::config(ConfigEnum::HttpLog, 'model') ?? HttpLog::class;

            $config = ConfigEnum::HttpLog;
            $connection = Lh::getConnection($config);
            $table = Lh::getTable($config);
            $data = $dto->serializeKeys(['type', 'method', 'status'])->toArray();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = $data['created_at'];
            $data = array_map(
                static fn ($item) => is_array($item) ? Hlp::castToJson($item) : $item,
                $data,
            );

            $columns = array_keys($data);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $columnsSql = implode(', ', array_map(static fn (string $c): string => "`$c`", $columns));

            DB::connection($connection)
                ->statement(
                    "insert into {$table} ({$columnsSql}) values ({$placeholders})",
                    array_values($data),
                );
        });
    }


    /**
     * Обновляет запись лога http запроса
     *
     * @param HttpLogUpdateDto|HttpLogFailedDto $dto
     * @return void
     */
    public function update(HttpLogUpdateDto|HttpLogFailedDto $dto): void
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
     * Удаляет записи логов http запросов старше указанного количества дней
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
