<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
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
        $data = Hlp::arrayDeleteKeys($data, $ignoreData);

        return Hlp::hashXxh128(
            $view . json_encode($data, Hlp::jsonFlags()) . json_encode($mergeData, Hlp::jsonFlags())
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
        ViewLogDto $dto,
        string $view,
        array $data = [],
        array $mergeData = [],
        array $ignoreData = [],
        int|bool|null $ttl = null,
    ): string {
        $render = static fn () => view($view, $data, $mergeData)->render();

        switch (true) {
            case $ttl === false:
            case in_array($view, $this->exclude):
                $result = $render();
                break;

            default:
                $dto->cacheKey = $this->getTagTtl($ttl) . '_' . $this->getCacheKey($view, $data, $mergeData, $ignoreData);

                if ($dto->isFromCache = Cache::driver($this->driver)->has($dto->cacheKey)) {
                    $result = Cache::driver($this->driver)->get($dto->cacheKey, '');

                } else {
                    $result = $render();

                    match (true) {
                        $ttl === 0 => Cache::driver($this->driver)->forever($dto->cacheKey, $result),
                        $ttl === true, is_null($ttl) => Cache::driver($this->driver)
                            ->set($dto->cacheKey, $result, config('laravel-helper.view_cache.ttl')),
                        default => Cache::driver($this->driver)->set($dto->cacheKey, $result, $ttl),
                    };

                    $dto->isCached = true;
                }

                $dto->cacheKey = Hlp::stringPadPrefix($dto->cacheKey, config('cache.prefix'));
                break;
        }

        return $result;
    }


    /**
     * Сбрасывает весь кеш рендеринга blade шаблонов
     *
     * @return void
     */
    public function flushViewCacheAll(): void
    {
        Cache::driver($this->driver)->flush();
    }
}
