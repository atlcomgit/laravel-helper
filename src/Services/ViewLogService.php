<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Repositories\ViewLogRepository;

/**
 * Сервис логирования рендеринга blade шаблонов
 */
class ViewLogService
{
    public function __construct(
        private ViewLogRepository $viewLogRepository,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Сохраняет запись рендеринга blade шаблона
     *
     * @param ViewLogDto $dto
     * @return void
     */
    public function log(ViewLogDto $dto): void
    {
        $dto->isUpdated
            ? $this->viewLogRepository->update($dto)
            : $this->viewLogRepository->create($dto);
    }


    /**
     * Очищает логи рендеринга blade шаблонов
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!config('laravel-helper.view_log.enabled')) {
            return 0;
        }

        return $this->viewLogRepository->cleanup($days);
    }


    /**
     * Создает dto для логирования рендеринга blade шаблона
     *
     * @return ViewLogDto
     */
    public function createViewLog(string $name): ViewLogDto
    {
        $dto = ViewLogDto::create(name: $name);
        config('laravel-helper.view_log.store_on_start') ?: $dto->dispatch();

        return $dto;
    }


    /**
     * Обновляет dto для логирования рендеринга blade шаблона
     *
     * @return void
     */
    public function updateViewLog(ViewLogDto $dto, string &$render): void
    {
        $dto->render = $render;
        $dto->isUpdated = config('laravel-helper.view_log.store_on_start');
        $dto->info = [
            ...($dto->info ?? []),
            'duration' => $dto->getDuration(),
            'memory' => $dto->getMemory(),
            'size_render' => Helper::stringLength($render),
        ];

        $dto->dispatch();
    }
}
