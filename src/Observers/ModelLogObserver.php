<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Observers;

use Atlcom\LaravelHelper\Services\ModelLogService;
use Illuminate\Database\Eloquent\Model;

class ModelLogObserver
{
    public function __construct(private ModelLogService $modelLogService) {}


    /**
     * Обработчик события при создании модели
     *
     * @param  Model  $model
     * @return void
     */
    public function created(Model $model)
    {
        !$model->logEnabled ?: $this->modelLogService->created($model);
    }


    /**
     * Обработчик события при обновлении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function updated(Model $model)
    {
        !$model->logEnabled ?: $this->modelLogService->updated($model);
    }


    /**
     * Обработчик события при удалении модели
     *
     * @param  Model  $model
     * @return void
     */
    public function deleted(Model $model)
    {
        !$model->logEnabled ?: $this->modelLogService->deleted($model);
    }


    /**
     * Обработчик события при полном удалении модели
     *
     * @param  Model  $model
     * @return void
     */
    //?!? 
    public function forceDeleted(Model $model)
    {
        // !$model->logEnabled ?: $this->modelLogService->forceDeleted($model);
    }


    /**
     * Handle the Model "restored" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function restored(Model $model)
    {
        !$model->logEnabled ?: $this->modelLogService->restored($model);
    }


    /**
     * Обработчик события при восстановлении модели
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function belongsToManyAttached($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->belongsToManyAttached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при присоединении belongsToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function belongsToManyDetached($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->belongsToManyDetached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot belongsToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function belongsToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->belongsToManyUpdatedExistingPivot($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при присоединении morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function morphToManyAttached($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->morphToManyAttached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при отсоединении morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function morphToManyDetached($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->morphToManyDetached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot morphToMany
     *
     * @param  string  $relation
     * @param  Model  $model
     * @param  array  $ids
     * @return void
     */
    //?!? 
    public function morphToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        // !$model->logEnabled ?: $this->modelLogService->morphToManyUpdatedExistingPivot($model, $relation, $model->{$relation}()->findMany($ids));
    }
}
