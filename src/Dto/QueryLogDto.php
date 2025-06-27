<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\QueryLogJob;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;

/**
 * Dto лога query запросов
 */
class QueryLogDto extends Dto
{
    public ?string $uuid;
    public int|string|null $userId;
    public ?string $name;
    public string $query;
    public ?string $cacheKey;
    public bool $isCached;
    public bool $isFromCache;
    public QueryLogStatusEnum $status;
    public ?float $duration;
    public ?int $memory;
    public ?int $count;
    public ?array $info;

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
            'status' => QueryLogStatusEnum::getDefault(),
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
        return QueryLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     */
    protected function mappings(): array
    {
        return [
            'userId' => 'user_id',
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
        $this->onlyKeys(QueryLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->excludeKeys(['startTime', 'startMemory', 'isUpdated']);
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
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            config('laravel-helper.query_log.queue_dispatch_sync')
                ? QueryLogJob::dispatchSync($this)
                : QueryLogJob::dispatch($this);
        }
    }
}
