<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;

/**
 * Трейт управления флагами кеширования, логирования и модельного лога
 *
 * Отвечает за установку/получение флагов и их синхронизацию
 * по цепочке EloquentBuilder → QueryBuilder → Connection.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutCache(int|bool|null $seconds = null)
 *
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutLog(?bool $enabled = null)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Connection
 */
trait QueryFlagsTrait
{
    /** Флаг включения кеширования запроса или ttl */
    protected int|bool|null $withQueryCache = null;

    /** Флаг включения лога query запроса */
    protected bool|null $withQueryLog = null;

    /** Флаг включения лога модели */
    protected bool|null $withModelLog = null;

    /** Класс, инициировавший кеширование запроса */
    private string|null $withQueryCacheClass = null;

    /** Класс, инициировавший логирование запроса */
    private string|null $withQueryLogClass = null;


    /**
     * Устанавливает флаг включения кеширования
     *
     * @param int|string|bool|null $seconds
     * @param int|bool|null $seconds - (int в секундах, null/true по умолчанию, false не сохранять)
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withQueryCache(int|string|bool|null $seconds = null): static
    {
        $now = now()->setTime(0, 0, 0, 0);
        !is_string($seconds) ?: $seconds = (int)abs(
            $now->copy()->modify(trim((string)$seconds, '- '))->diffInSeconds($now),
        );
        $this->setQueryCache($seconds ?? true);
        ($seconds === false) ?: $this->setQueryCacheClass(null, true);

        return $this;
    }


    /**
     * Устанавливает флаг включения лога query запроса
     *
     * @param bool|null $enabled
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withQueryLog(bool|null $enabled = null): static
    {
        $this->setQueryLog($enabled ?? true);
        ($enabled === false) ?: $this->setQueryLogClass(null, true);

        return $this;
    }


    /**
     * Устанавливает флаг включения лога модели
     *
     * @param bool|null $enabled
     * @return static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function withModelLog(bool|null $enabled = null): static
    {
        $this->setModelLog($enabled ?? true);

        return $this;
    }


    /**
     * Устанавливает флаг включения кеша query по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param int|string|bool|null $seconds
     * @return void
     */
    public function setQueryCache(int|string|bool|null $seconds): void
    {
        $this->withQueryCache = $seconds;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryCache($this->withQueryCache);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryCache($this->withQueryCache);
        }
    }


    /**
     * Устанавливает флаг включения лога query по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param bool|null $enabled
     * @return void
     */
    public function setQueryLog(bool|null $enabled): void
    {
        $this->withQueryLog = $enabled;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryLog($this->withQueryLog);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryLog($this->withQueryLog);
        }
    }


    /**
     * Устанавливает флаг включения лога модели по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @param bool|null $enabled
     * @return void
     */
    public function setModelLog(bool|null $enabled): void
    {
        $this->withModelLog = $enabled;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setModelLog($this->withModelLog);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setModelLog($this->withModelLog);
        }
    }


    /**
     * Синхронизирует флаги по цепочке EloquentBuilder->QueryBuilder->Connection
     *
     * @return void
     */
    public function syncQueryProperties(): void
    {
        $this->setQueryCache($this->withQueryCache);
        $this->setQueryLog($this->withQueryLog);
        $this->setModelLog($this->withModelLog);
    }


    /**
     * Устанавливает класс вызвавший кеш первого query запроса
     *
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string|null $class
     * @param bool $reset
     * @return static
     */
    public function setQueryCacheClass(?string $class, bool $reset = false): static
    {
        !(is_null($this->withQueryCacheClass) || $reset) ?: $this->withQueryCacheClass = $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryCacheClass($this->withQueryCacheClass, $reset);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryCacheClass($this->withQueryCacheClass, $reset);
        }

        return $this;
    }


    /**
     * Устанавливает класс вызвавший лог первого query запроса
     *
     * Последовательность вызовов: EloquentBuilder -> QueryBuilder -> Connection
     *
     * @param string|null $class
     * @param bool $reset
     * @return static
     */
    public function setQueryLogClass(?string $class, bool $reset = false): static
    {
        !(is_null($this->withQueryLogClass) || $reset) ?: $this->withQueryLogClass = $class;

        if ($this instanceof EloquentBuilder) {
            $this->getQuery()->setQueryLogClass($this->withQueryLogClass, $reset);
        }
        if ($this instanceof QueryBuilder) {
            $this->getConnection()->setQueryLogClass($this->withQueryLogClass, $reset);
        }

        return $this;
    }


    /**
     * Возвращает кешируемый класс
     *
     * @return string|null
     */
    public function getQueryCacheClass(): ?string
    {
        return $this->withQueryCacheClass;
    }


    /**
     * Возвращает логируемый класс
     *
     * @return string|null
     */
    public function getQueryLogClass(): ?string
    {
        return $this->withQueryLogClass;
    }


    /**
     * Возвращает название тега из ttl (дополнительно добавляется в ключ кеша)
     *
     * @param int|bool|null $ttl
     * @return string
     */
    protected function getTagTtl(int|bool|null $ttl): string
    {
        $ttl ??= $this->withQueryCache;

        return match (true) {
            is_null($ttl) || $ttl === 0 => 'ttl_not_set',
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",

            default => '',
        };
    }


    /**
     * Возвращает массив игнорируемых таблиц для кеша и лога
     *
     * @return array
     */
    public function getIgnoreTables(): array
    {
        // Используем runtime-кеш вместо static для совместимости с Octane/Swoole
        return Hlp::cacheRuntime(
            'QueryFlagsTrait::getIgnoreTables',
            static fn () => [
                Lh::config(ConfigEnum::ConsoleLog, 'table'),
                Lh::config(ConfigEnum::HttpLog, 'table'),
                Lh::config(ConfigEnum::ModelLog, 'table'),
                Lh::config(ConfigEnum::ProfilerLog, 'table'),
                Lh::config(ConfigEnum::RouteLog, 'table'),
                Lh::config(ConfigEnum::QueryLog, 'table'),
                Lh::config(ConfigEnum::QueueLog, 'table'),
                Lh::config(ConfigEnum::ViewLog, 'table'),
            ],
        );
    }
}
