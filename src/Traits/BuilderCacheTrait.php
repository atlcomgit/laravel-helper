<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 * @mixin \Illuminate\Database\Eloquent\Builder, \Illuminate\Database\Query\Builder
 */
trait BuilderCacheTrait
{
    /** Включает кеширование запроса */
    protected int|bool|null $withCache = null;


    /**
     * Устанавливает флаг подключения кеширования
     *
     * @param int|bool|null $seconds
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function setWithCache(int|bool|null $seconds = null): static
    {
        //?!? $seconds
        $this->withCache = $value ?? true;

        return $this;
    }


    /**
     * Возвращает флаг подключения кеширования
     *
     * @return int|bool|null
     */
    public function getWithCache(): int|bool|null
    {
        return $this->withCache;
    }
}
