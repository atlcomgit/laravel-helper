<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\MailLog;

/**
 * Репозиторий логов отправки писем
 */
class MailLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var MailLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::MailLog, 'model') ?? MailLog::class;
    }


    /**
     * Создает запись лога
     *
     * @param MailLogDto $dto
     * @return MailLog
     */
    public function create(MailLogDto $dto): MailLog
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->create($dto->toArray())
        );
    }


    /**
     * Обновляет запись лога
     *
     * @param MailLogDto $dto
     * @return MailLog|null
     */
    public function update(MailLogDto $dto): ?MailLog
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
     * Очищает логи
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
