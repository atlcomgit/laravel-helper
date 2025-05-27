<?php

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Services\ModelLogService;

/**
 * Трейт для логирования модели
 * 
 * @property bool $logEnabled
 * @property array $logExcludeAttributes
 * @property array $logHideAttributes
 */
trait ModelLogTrait
{
    public bool $logEnabled = true;


    /**
     * Автозагрузка статических методов у модели
     *
     * @return void
     */
    protected static function bootModelLogTrait()
    {
        $model = new static();
        $logEnabled = property_exists($model, 'logEnabled') ? $model->logEnabled : false;

        if ($logEnabled && config('laravel-helper.model_log.enabled') && static::class !== ModelLog::class) {
            static::created(static function ($model) {
                (new ModelLogService($model))->created();
            });

            static::updated(static function ($model) {
                (new ModelLogService($model))->updated();
            });

            static::deleted(static function ($model) {
                (new ModelLogService($model))->deleted();
            });

            if (method_exists(static::class, 'restored')) {
                static::restored(static function ($model) {
                    (new ModelLogService($model))->restored();
                });
            }
        }
    }
}
