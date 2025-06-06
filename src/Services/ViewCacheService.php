<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис кеширования рендеринга blade шаблонов
 */
class ViewCacheService
{
    protected string $driver = '';
    protected array $exclude = [];


    public function __construct()
    {
        $this->driver = config('laravel-helper.query_cache.driver') ?: config('cache.default');
        $this->exclude = config('laravel-helper.view_cache.exclude') ?? [];
    }


    /**
     * Возвращает название тега из ttl (дополнительно добавляется в ключ кеша)
     *
     * @param int|bool|null $ttl
     * @return string
     */
    protected function getTagTtl(int|bool|null $ttl): string
    {
        return match (true) {
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",
            is_null($ttl) => 'ttl_not_set',

            default => '',
        };

    }


    /**
     * Возвращает ключ кеша
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @param array $ignoreData
     * @param int|bool|null $ttl
     * @return string
     */
    public function getCacheKey(string $view, array $data = [], array $mergeData = [], array $ignoreData = []): string
    {
        $data = Helper::arrayDeleteKeys($data, $ignoreData);

        return Helper::hashXxh128(
            $view . json_encode($data, Helper::jsonFlags()) . json_encode($mergeData, Helper::jsonFlags())
        );
    }


    /**
     * Возвращает рендеринг шаблона с использованием кеша
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @param array $ignoreData
     * @param int|bool|null $ttl
     * @return string
     */
    public function remember(
        string $view,
        array $data = [],
        array $mergeData = [],
        array $ignoreData = [],
        int|bool|null $ttl = null,
    ): string {
        $cacheKey = $this->getTagTtl($ttl) . '_' . $this->getCacheKey($view, $data, $mergeData, $ignoreData);
        $render = static fn () => view($view, $data, $mergeData)->render();

        return match (true) {
            $ttl === false => $render(),
            in_array($view, $this->exclude) => $render(),
            $ttl === true || is_null($ttl) => Cache::driver($this->driver)
                ->remember($cacheKey, config('laravel-helper.query_cache.ttl'), $render),
            $ttl === 0 => Cache::driver($this->driver)->rememberForever($cacheKey, $render),

            default => Cache::driver($this->driver)->remember($cacheKey, $ttl, $render),
        };
    }
}
