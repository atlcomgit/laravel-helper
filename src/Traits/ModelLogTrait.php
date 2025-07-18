<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;

/**
 * Трейт для подключения логирования модели
 * 
 * @property bool|null $withModelLog
 * @property-read array $modelLogExcludeAttributes
 * @property-read array $modelLogHiddenAttributes
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultModel
 */
trait ModelLogTrait
{
    /** Флаг включения лога модели */
    protected ?bool $withModelLog = null;
    /** Массив полей для исключения из лога */
    public array $modelLogExcludeAttributes = [];
    /** Массив полей для скрытого значения в логе */
    public array $modelLogHiddenAttributes = ['password'];


    /**
     * Вызывает макрос подключения логирования модели
     *
     * @param bool|null $enabled
     * @return EloquentBuilder<static>
     */
    public static function withModelLog(?bool $enabled = null): EloquentBuilder
    {
        $query = static::query()->withModelLog($enabled);
        $query->getQuery()->withModelLog($enabled);
        $query->getModel()->setWithModelLogAttribute($enabled);

        return $query;
    }


    /**
     * Устанавливает флаг включения лога модели
     *
     * @param bool|null $enabled
     * @return static
     */
    public function setWithModelLogAttribute(?bool $enabled = null): static
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
        return Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                    || ($this->withModelLog !== false && Lh::config(ConfigEnum::ModelLog, 'global'))
            );
    }


    /**
     * Автозагрузка трейта
     *
     * @return void
     */
    protected static function bootModelLogTrait()
    {
        if (
            Lh::config(ConfigEnum::ModelLog, 'enabled')
            && property_exists(static::class, 'withModelLog')
            && static::class !== ModelLog::class
        ) {
            static::observe(ModelLogObserver::class);
        }
    }
}
