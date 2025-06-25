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
    protected bool $gzdeflateEnabled = false;
    protected int $gzdeflateLevel = -1;


    public function __construct()
    {
        $this->driver = config('laravel-helper.query_cache.driver') ?: config('cache.default');
        $this->exclude = config('laravel-helper.view_cache.exclude') ?? [];
        $this->gzdeflateEnabled = config('laravel-helper.view_cache.gzdeflate.enabled') ?? false;
        $this->gzdeflateLevel = config('laravel-helper.view_cache.gzdeflate.level') ?? -1;
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
            is_null($ttl) || $ttl === 0 => 'ttl_not_set',
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",

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
        $gzdeflateEnabled = $this->gzdeflateEnabled;
        $gzdeflateLevel = $this->gzdeflateLevel;

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

                    !(is_string($result) && $this->gzdeflateEnabled)
                        ?: $result = (($tmp = @gzinflate($result)) === false) ? '' : $tmp;

                } else {
                    $result = $render();
                    $cache = $this->gzdeflateEnabled
                        ? $cache = gzdeflate($result, $this->gzdeflateLevel)
                        : $result;

                    match (true) {
                        $ttl === 0 => Cache::driver($this->driver)->forever($dto->cacheKey, $cache),
                        $ttl === true, is_null($ttl) => Cache::driver($this->driver)
                            ->set($dto->cacheKey, $cache, config('laravel-helper.view_cache.ttl')),

                        default => Cache::driver($this->driver)->set($dto->cacheKey, $cache, $ttl),
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
