<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\ViewLogJob;
use Atlcom\LaravelHelper\Models\ViewLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * Dto лога рендеринга blade шаблонов
 */
class ViewLogDto extends Dto
{
    public ?string $uuid;
    public string $name;
    public ?array $data;
    public ?array $mergeData;
    public ?string $render;
    public ?string $cacheKey;
    public bool $isCached;
    public bool $isFromCache;
    public ViewLogStatusEnum $status;
    public ?array $info;


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
            'status' => ViewLogStatusEnum::getDefault(),
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
        return ViewLog::getModelCasts();
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'mergeData' => 'merge_data',
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
        $this->onlyKeys(ViewLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull();
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (
            !config('laravel-helper.view_log.enabled')
            || app(LaravelHelperService::class)->checkIgnoreTables([ViewLog::getTableName()])
            || app(LaravelHelperService::class)
                ->checkExclude('laravel-helper.view_log.exclude', $this->serializeKeys(true)->toArray())
        ) {
            return;
        }

        isTesting()
            ? ViewLogJob::dispatchSync($this)
            : ViewLogJob::dispatch($this);
    }
}
