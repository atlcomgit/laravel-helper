<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Services\ViewCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DefaultController extends Controller
{
    /** Флаг кеширования рендеринга blade шаблонов */
    protected int|bool|null $useWithCache = false;


    /**
     * Устанавливает флаг кеширования рендеринга blade шаблонов
     *
     * @param int|bool|null $seconds
     * @return static
     */
    public function withCache(int|bool|null $seconds = null): static
    {
        $this->useWithCache = $seconds;

        return $this;
    }


    //?!? дополнить withLog


    /**
     * Возвращает рендеринг шаблона с использованием кеша
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @param array $ignoreData - массив игнорируемых ключей в data при генерации ключа кеша
     * @return View|string
     */
    public function view(
        string $view,
        array $data = [],
        array $mergeData = [],
        array $ignoreData = [],
    ): string|View {
        return (config('laravel-helper.view_cache.enabled') && $this->useWithCache !== false)
            ? app(ViewCacheService::class)
                ->remember($view, $data, $mergeData, $ignoreData, $this->useWithCache)
            : view($view, $data, $mergeData);
    }
}
