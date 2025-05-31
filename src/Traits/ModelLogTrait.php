<?php

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
use Atlcom\LaravelHelper\Services\ModelLogService;

/**
 * Трейт для подключения логирования модели
 * 
 * @property bool $logEnabled
 * @property array $logExcludeAttributes
 * @property array $logHideAttributes
 */
trait ModelLogTrait
{
    public bool $logEnabled = true;


    /**
     * Автозагрузка трейта
     *
     * @return void
     */
    protected static function bootModelLogTrait()
    {
        $model = new static();
        $logEnabled = property_exists($model, 'logEnabled') ? $model->logEnabled : false;

        if ($logEnabled && config('laravel-helper.model_log.enabled') && static::class !== ModelLog::class) {
            static::observe(ModelLogObserver::class);
        }
    }
}
