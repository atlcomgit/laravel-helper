<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ViewCacheEventDto;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;
use Atlcom\LaravelHelper\Events\ViewCacheEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис кеширования рендеринга blade шаблонов
 */
class ViewCacheService extends DefaultService
{
    protected string $driver = '';
    protected array $exclude = [];
    protected bool $gzdeflateEnabled = false;
    protected int $gzdeflateLevel = -1;


    public function __construct()
    {
        $this->driver = Lh::config(ConfigEnum::ViewCache, 'driver') ?: config('cache.default');
        $this->exclude = Lh::config(ConfigEnum::ViewCache, 'exclude') ?? [];
        $this->gzdeflateEnabled = Lh::config(ConfigEnum::ViewCache, 'gzdeflate.enabled') ?? false;
        $this->gzdeflateLevel = Lh::config(ConfigEnum::ViewCache, 'gzdeflate.level') ?? -1;
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
                $result = $this->withoutTelescope(
                    function () use (&$dto, &$view, &$data, &$mergeData, &$ignoreData, &$ttl, &$render) {
                        $dto->cacheKey = $this->getTagTtl($ttl)
                            . '_' . $this->getCacheKey($view, $data, $mergeData, $ignoreData);

                        if ($dto->isFromCache = Cache::driver($this->driver)->has($dto->cacheKey)) {
                            $result = Cache::driver($this->driver)->get($dto->cacheKey, '');

                            !(is_string($result) && $this->gzdeflateEnabled)
                                ?: $result = (($tmp = @gzinflate($result)) === false) ? '' : $tmp;

                            event(
                                new ViewCacheEvent(
                                    ViewCacheEventDto::create(
                                        type: EventTypeEnum::GetViewCache,
                                        key: $dto->cacheKey,
                                        view: $view,
                                        data: $data,
                                        mergeData: $mergeData,
                                        ignoreData: $ignoreData,
                                        render: $result,
                                    ),
                                ),
                            );

                        } else {
                            $result = $render();
                            $cache = $this->gzdeflateEnabled
                                ? $cache = gzdeflate($result, $this->gzdeflateLevel)
                                : $result;

                            match (true) {
                                $ttl === 0 => Cache::driver($this->driver)->forever($dto->cacheKey, $cache),
                                $ttl === true, is_null($ttl) => Cache::driver($this->driver)
                                    ->set($dto->cacheKey, $cache, Lh::config(ConfigEnum::ViewCache, 'ttl')),

                                default => Cache::driver($this->driver)->set($dto->cacheKey, $cache, $ttl),
                            };

                            $dto->isCached = true;

                            event(
                                new ViewCacheEvent(
                                    ViewCacheEventDto::create(
                                        type: EventTypeEnum::SetViewCache,
                                        key: $dto->cacheKey,
                                        view: $view,
                                        data: $data,
                                        mergeData: $mergeData,
                                        ignoreData: $ignoreData,
                                        ttl: $ttl,
                                        render: $result,
                                    ),
                                ),
                            );
                        }

                        $dto->cacheKey = Hlp::stringPadPrefix($dto->cacheKey, config('cache.prefix'));
                        
                        return $result;
                    }
                );
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
        $this->withoutTelescope(
            function () {
                Cache::driver($this->driver)->flush();

                event(
                    new ViewCacheEvent(
                        ViewCacheEventDto::create(
                            type: EventTypeEnum::FlushViewCache,
                        ),
                    ),
                );
            }
        );

    }
}
