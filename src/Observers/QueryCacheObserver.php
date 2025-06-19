<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Observers;

use Atlcom\LaravelHelper\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Model;

class QueryCacheObserver
{
    public function __construct(private QueryCacheService $queryCacheService) {}


    /**
     * Обработчик события при создании модели
     *
     * @param  Model  $model
     * @return void
     */
    public function created(Model $model)
    {
        $this->queryCacheService->flushQueryCache($model);
    }


    /**
     * Обработчик события при обновлении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function updated(Model $model)
    {
        $this->queryCacheService->flushQueryCache($model);
    }


    /**
     * Обработчик события при удалении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function deleted(Model $model)
    {
        $this->queryCacheService->flushQueryCache($model);
    }


    /**
     * Обработчик события при полном удалении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function forceDeleted(Model $model)
    {
        $this->queryCacheService->flushQueryCache($model);
    }


    /**
     * Обработчик события при восстановлении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function restored(Model $model)
    {
        $this->queryCacheService->flushQueryCache($model);
    }


    /**
     * Обработчик события при присоединении belongsToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function belongsToManyAttached($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при отсоединении belongsToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function belongsToManyDetached($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot belongsToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function belongsToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при присоединении morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function morphToManyAttached($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при отсоединении morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function morphToManyDetached($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    public function morphToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        $this->queryCacheService->flushQueryCache($model, $relation, $model->{$relation}()->findMany($ids));
    }
}
