<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Observers;

use Atlcom\LaravelHelper\Services\ModelLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 * Наблюдатель за моделями
 */
class ModelLogObserver
{
    public function __construct(private ModelLogService $modelLogService) {}


    /**
     * Обработчик события при создании модели
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function created(Model $model, ?array $attributes = null)
    {
        !$model->isWithModelLog() ?: $this->modelLogService->created($model, $attributes);
    }


    /**
     * Обработчик события при обновлении модели
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function updated(Model $model, ?array $attributes = null)
    {
        !($model->isWithModelLog() && !is_null($attributes)) ?: $this->modelLogService->updated($model, $attributes);
    }


    /**
     * Обработчик события при удалении модели
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function deleted(Model $model, ?array $attributes = null)
    {
        !$model->isWithModelLog() ?: $this->modelLogService->deleted($model);
    }


    /**
     * Обработчик события при полном удалении модели
     *
     * @param Model $model
     * @return void
     */
    public function forceDeleted(Model $model)
    {
        // not need !$model->isWithModelLog() ?: $this->modelLogService->forceDeleted($model);
    }


    /**
     * Обработчик события при восстановлении модели
     *
     * @param Model $model
     * @return void
     */
    public function restored(Model $model)
    {
        !$model->isWithModelLog() ?: $this->modelLogService->restored($model);
    }


    /**
     * Обработчик события при очистке таблицы модели
     *
     * @param Model $model
     * @return void
     */
    public function truncated(Model $model)
    {
        !$model->isWithModelLog() ?: $this->modelLogService->truncated($model);
    }


    /**
     * Обработчик события при восстановлении модели
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function belongsToManyAttached($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->belongsToManyAttached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при присоединении belongsToMany
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function belongsToManyDetached($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->belongsToManyDetached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot belongsToMany
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function belongsToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->belongsToManyUpdatedExistingPivot($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при присоединении morphToMany
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function morphToManyAttached($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->morphToManyAttached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при отсоединении morphToMany
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function morphToManyDetached($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->morphToManyDetached($model, $relation, $model->{$relation}()->findMany($ids));
    }


    /**
     * Обработчик события при обновлении pivot morphToMany
     *
     * @param string  $relation
     * @param Model $model
     * @param array $ids
     * @return void
     */
    //?!? observer дополнить
    public function morphToManyUpdatedExistingPivot($relation, Model $model, $ids)
    {
        // !$model->isWithModelLog() ?: $this->modelLogService->morphToManyUpdatedExistingPivot($model, $relation, $model->{$relation}()->findMany($ids));
    }
}
