<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\ProfilerLogJob;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Carbon\Carbon;
use Throwable;

/**
 * Dto профилирования методов класса
 */
class ProfilerLogDto extends DefaultDto
{
    public ?string $uuid;
    public ?string $class;
    public ?string $method;
    public bool $isStatic;
    public ?array $arguments;
    public ProfilerLogStatusEnum $status;
    public mixed $result;
    public string|Throwable|null $exception;
    public int $count;
    public ?float $duration;
    public ?int $memory;
    public ?array $info;

    public ?string $startTime;
    public ?int $startMemory;
    public ?float $durationMin;
    public ?float $durationAvg;
    public ?float $durationMax;
    public ?int $memoryMin;
    public ?int $memoryAvg;
    public ?int $memoryMax;
    public bool $isUpdated;


    /**
     * @inheritDoc
     * @see parent::mappings()
     *
     * @return array
     */
    // #[Override()]
    protected function mappings(): array
    {
        return [
            'isStatic' => 'is_static',
        ];
    }


    /**
     * @inheritDoc
     * @see parent::defaults()
     */
    protected function defaults(): array
    {
        return [
            'uuid' => uuid(),
            'status' => ProfilerLogStatusEnum::getDefault(),
            'count' => 0,

            'isUpdated' => false,
        ];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return ProfilerLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onSerializing()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(ProfilerLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->excludeKeys([
                'startTime',
                'startMemory',
                'durationMin',
                'durationAvg',
                'durationMax',
                'memoryMin',
                'memoryAvg',
                'memoryMax',
                'isUpdated',
            ])->includeArray([
                    'result' => Hlp::stringReplace(trim(json($this->result), '"'), ['\"' => '"']),
                ]);
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return float
     */
    public function getDuration(): float
    {
        return max(0, Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000);
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return int
     */
    public function getMemory(): int
    {
        return max(0, memory_get_usage() - $this->startMemory);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::ProfilerLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? ProfilerLogJob::dispatchSync($this)
                : ProfilerLogJob::dispatch($this);
            $this->isUpdated = true;
        }

        return $this;
    }
}
