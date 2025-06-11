<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Throwable;

class DefaultController extends Controller
{
    /** Флаг кеширования рендеринга blade шаблонов */
    protected int|bool|null $useWithCache = false;

    /** Флаг логирования рендеринга blade шаблонов */
    protected bool|null $useWithLog = false;


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


    /**
     * Устанавливает флаг логирования рендеринга blade шаблонов
     *
     * @param bool|null $enabled
     * @return static
     */
    public function withLog(bool|null $enabled = null): static
    {
        $this->useWithCache = $enabled ?? true;

        return $this;
    }


    /**
     * Возвращает рендеринг шаблона с использованием кеша
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @param array $ignoreData - массив игнорируемых ключей в data при генерации ключа кеша
     * @return string
     */
    public function view(
        string $view,
        array $data = [],
        array $mergeData = [],
        array $ignoreData = [],
    ): string {
        try {
            $render = '';
            $dto = app(ViewLogService::class)->createViewLog(name: $view);


            if (config('laravel-helper.view_cache.enabled') && $this->useWithCache !== false) {
                $render = app(ViewCacheService::class)
                    ->remember($dto, $view, $data, $mergeData, $ignoreData, $this->useWithCache);

            } else {
                $render = view($view, $data, $mergeData)->render();
            }

            $dto->status = ViewLogStatusEnum::Success;

        } catch (Throwable $exception) {
            $dto->status = ViewLogStatusEnum::Failed;
            $dto->info = [
                ...($dto->info ?? []),
                'exception' => Helper::exceptionToArray($exception),
            ];

            throw $exception;
        } finally {
            app(ViewLogService::class)->updateViewLog($dto, $render);
        }

        return $render;
    }
}
