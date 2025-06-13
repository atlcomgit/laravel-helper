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
    public ?string $name;
    public string $query;
    public ?string $cacheKey;
    public bool $isCached;
    public bool $isFromCache;
    public QueryLogStatusEnum $status;
    public ?array $info;

    public string $startTime;
    public int $startMemory;
    public bool $isUpdated;


    /**
     * @override
     * Возвращает массив значений по умолчанию
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'uuid' => uuid(),
            'isCached' => false,
            'isFromCache' => false,
            'status' => QueryLogStatusEnum::getDefault(),
            'startTime' => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
            'isUpdated' => false,
        ];
    }


    /**
     * Возвращает массив преобразований типов
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
     */
    protected function mappings(): array
    {
        return [
            'cacheKey' => 'cache_key',
            'isCached' => 'is_cached',
            'isFromCache' => 'is_from_cache',
        ];
    }


    /**
     * Метод вызывается до преобразования dto в массив
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
     * @return string
     */
    public function getDuration(): string
    {
        return Hlp::timeSecondsToString(
            value: Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000,
            withMilliseconds: false,
        );
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return string
     */
    public function getMemory(): string
    {
        return Hlp::sizeBytesToString(memory_get_usage() - $this->startMemory);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            isTesting()
                ? QueryLogJob::dispatchSync($this)
                : QueryLogJob::dispatch($this);
        }
    }
}
