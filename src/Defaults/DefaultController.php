<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Atlcom\LaravelHelper\Services\ViewLogService;
use Illuminate\Routing\Controller;
use Throwable;

class DefaultController extends Controller
{
    /** Флаг кеширования рендеринга blade шаблонов */
    protected int|bool|null $withViewCache = false;

    /** Флаг логирования рендеринга blade шаблонов */
    protected bool|null $withViewLog = false;


    /**
     * Устанавливает флаг кеширования рендеринга blade шаблонов
     *
     * @param int|bool|null $seconds
     * @return static
     */
    public function withViewCache(int|bool|null $seconds = null): static
    {
        $this->withViewCache = $seconds;

        return $this;
    }


    /**
     * Устанавливает флаг логирования рендеринга blade шаблонов
     *
     * @param bool|null $enabled
     * @return static
     */
    public function withViewLog(bool|null $enabled = null): static
    {
        $this->withViewLog = $enabled ?? true;

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
            $dto = app(ViewLogService::class)->createViewLog(name: $view, withViewLog: $this->withViewLog);


            $render = (config('laravel-helper.view_cache.enabled') && $this->withViewCache !== false)
                ? app(ViewCacheService::class)
                    ->remember($dto, $view, $data, $mergeData, $ignoreData, $this->withViewCache)
                : view($view, $data, $mergeData)->render();

            $dto->status = ViewLogStatusEnum::Success;

        } catch (Throwable $exception) {
            $dto->status = ViewLogStatusEnum::Failed;
            $dto->info = [
                ...($dto->info ?? []),
                'exception' => Hlp::exceptionToArray($exception),
            ];

            throw $exception;
        } finally {
            app(ViewLogService::class)->updateViewLog($dto, $render);
        }

        return $render;
    }
}
