<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

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
}
