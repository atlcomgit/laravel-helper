<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ViewCacheDto;
use Atlcom\LaravelHelper\Dto\ViewCacheEventDto;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;
use Atlcom\LaravelHelper\Events\ViewCacheEvent;
use Atlcom\LaravelHelper\Facades\Lh;

/**
 * Сервис кеширования рендеринга blade шаблонов
 */
class ViewCacheService extends DefaultService
{
    protected CacheService $cacheService;
    protected array $exclude = [];


    public function __construct()
    {
        $this->cacheService = app(CacheService::class);
        $this->exclude = Lh::config(ConfigEnum::ViewCache, 'exclude') ?? [];
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
     * @return string|null
     */
    public function viewCache(
        ViewLogDto $dto,
        string $view,
        array $data = [],
        array $mergeData = [],
        array $ignoreData = [],
        int|bool|null $ttl = null,
    ): ?string {
        $render = static fn () => (view($view, $data, $mergeData)->render() ?: '');

        switch (true) {
            case $ttl === false:
            case in_array($view, $this->exclude):
                return $render();

            default:
                $viewCacheDto = ViewCacheDto::create(
                    key: $dto->cacheKey = $this->getTagTtl($ttl)
                    . '_' . $this->getCacheKey($view, $data, $mergeData, $ignoreData),
                    ttl: $this->cacheService->getCacheTtl($ttl),
                    view: $view,
                    data: $data,
                    mergeData: $mergeData,
                    ignoreData: $ignoreData,
                );

                if ($this->hasViewCache($viewCacheDto)) {
                    $this->getViewCache($viewCacheDto);
                    $dto->isFromCache = true;

                    return $viewCacheDto->render;
                }

                $viewCacheDto->render = $render();
                $this->setViewCache($viewCacheDto);
                $dto->isCached = true;

                return $viewCacheDto->render;
        }
    }


    /**
     * Проверяет наличие ключа рендеринга blade шаблона в кеше 
     *
     * @param ViewCacheDto $dto
     * @return bool
     */
    public function hasViewCache(ViewCacheDto $dto): bool
    {
        if (!$dto->key) {
            return false;
        }

        return $this->cacheService->hasCache(ConfigEnum::ViewCache, [], $dto->key);
    }


    /**
     * Сохраняет ключ рендеринга blade шаблона в кеше
     *
     * @param ViewCacheDto $dto
     * @return void
     */
    public function setViewCache(ViewCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                ($dto->ttl === false)
                    ?: $this->cacheService
                        ->setCache(ConfigEnum::ViewCache, $dto->tags, $dto->key, $dto->render, $dto->ttl);

                event(
                    new ViewCacheEvent(
                        ViewCacheEventDto::create(
                            type: EventTypeEnum::SetViewCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            ttl: $dto->ttl,
                            view: $dto->view,
                            data: $dto->data,
                            mergeData: $dto->mergeData,
                            ignoreData: $dto->ignoreData,
                            render: $dto->render,
                        ),
                    ),
                );
            }
        );
    }


    /**
     * Возвращает ключ рендеринга blade шаблона из кеша
     *
     * @param ViewCacheDto $dto
     * @return void
     */
    public function getViewCache(ViewCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                $dto->render = $this->cacheService->getCache(ConfigEnum::ViewCache, $dto->tags, $dto->key, null);

                event(
                    new ViewCacheEvent(
                        ViewCacheEventDto::create(
                            type: EventTypeEnum::GetViewCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            ttl: $dto->ttl,
                            view: $dto->view,
                            data: $dto->data,
                            mergeData: $dto->mergeData,
                            ignoreData: $dto->ignoreData,
                            render: $dto->render,
                        ),
                    ),
                );
            }
        );
    }


    /**
     * Удаляет ключи рендеринга blade шаблона из кеша по тегам
     *
     * @param array $tags
     * @return void
     */
    public function flushViewCache(array $tags = []): void
    {
        $this->withoutTelescope(
            function () use (&$tags) {
                $this->cacheService->flushCache(ConfigEnum::ViewCache, $tags);

                event(
                    new ViewCacheEvent(
                        ViewCacheEventDto::create(
                            type: EventTypeEnum::FlushViewCache,
                            tags: $tags,
                        ),
                    ),
                );
            }
        );
    }


    /**
     * Удаляет все ключи рендеринга blade шаблона из кеша
     *
     * @return void
     */
    public function flushViewCacheAll(): void
    {
        $this->withoutTelescope(
            function () {
                $this->cacheService->flushCache(ConfigEnum::ViewCache, $tags = [ConfigEnum::ViewCache->value]);

                event(
                    new ViewCacheEvent(
                        ViewCacheEventDto::create(
                            type: EventTypeEnum::FlushViewCache,
                            tags: $tags,
                        ),
                    ),
                );
            }
        );
    }
}
