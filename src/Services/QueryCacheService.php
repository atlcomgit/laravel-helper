<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Сервис кеширования query запросов
 */
class QueryCacheService
{
    /**
     * Сбрасывает кеш
     *
     * @param Model $model
     * @param string|null $relation
     * @param Collection|null $pivotedModels
     * @return void
     */
    public function flush(Model $model, ?string $relation = null, ?Collection $pivotedModels = null)
    {
        //?!? сброс кеша
    }


    public function setCache()
    {
        
    }
}