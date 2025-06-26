<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\ViewLogJob;
use Atlcom\LaravelHelper\Models\ViewLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;

/**
 * Dto лога рендеринга blade шаблонов
 */
class ViewLogDto extends Dto
{
    public ?string $uuid;
    public int|string|null $userId;
    public string $name;
    public ?array $data;
    public ?array $mergeData;
    public ?string $render;
    public ?string $cacheKey;
    public bool $isCached;
    public bool $isFromCache;
    public ViewLogStatusEnum $status;
    public ?float $duration;
    public ?int $memory;
    public ?array $info;

    public ?bool $withViewLog;
    public string $startTime;
    public int $startMemory;
    public bool $isUpdated;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'uuid' => uuid(),
            'userId' => user(returnOnlyId: true),
            'isCached' => false,
            'isFromCache' => false,
            'status' => ViewLogStatusEnum::getDefault(),

            'withViewLog' => false,
            'startTime' => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
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
        return ViewLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     */
    protected function mappings(): array
    {
        return [
            'userId' => 'user_id',
            'mergeData' => 'merge_data',
            'cacheKey' => 'cache_key',
            'isCached' => 'is_cached',
            'isFromCache' => 'is_from_cache',
        ];
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
        $this->onlyKeys(ViewLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->excludeKeys(['withViewLog', 'startTime', 'startMemory', 'isUpdated']);
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return float
     */
    public function getDuration(): float
    {
        return Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000;
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
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this) && $this->withViewLog) {
            config('laravel-helper.view_log.queue_dispatch_sync')
                ? ViewLogJob::dispatchSync($this)
                : ViewLogJob::dispatch($this);
        }
    }
}
