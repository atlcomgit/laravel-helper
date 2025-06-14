<?php

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;

/**
 * Трейт для подключения логирования модели
 * 
 * @property bool $modelLog
 * @property array $logExcludeAttributes
 * @property array $logHideAttributes
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultModel
 */
trait ModelLogTrait
{
    /** Флаг включения лога модели */
    protected ?bool $withModelLog = null;


    /**
     * Устанавливает флаг включения лога модели
     *
     * @param bool|null $enabled
     * @return static
     */
    public function withModelLog(?bool $enabled = null): static
    {
        $this->withModelLog = $enabled ?? true;

        return $this;
    }


    /**
     * Возвращает значение флага лога модели
     *
     * @return bool|null
     */
    public function isWithModelLog(): ?bool
    {
        return $this->withModelLog;
    }


    /**
     * Автозагрузка трейта
     *
     * @return void
     */
    protected static function bootModelLogTrait()
    {
        if (
            config('laravel-helper.model_log.enabled')
            && property_exists(static::class, 'withModelLog')
            && static::class !== ModelLog::class
        ) {
            static::observe(ModelLogObserver::class);
        }
    }
}
