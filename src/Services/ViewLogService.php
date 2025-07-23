<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Repositories\ViewLogRepository;

/**
 * Сервис логирования рендеринга blade шаблонов
 */
class ViewLogService extends DefaultService
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
        if (!Lh::config(ConfigEnum::ViewLog, 'enabled')) {
            return 0;
        }

        return $this->viewLogRepository->cleanup($days);
    }


    /**
     * Создает dto для логирования рендеринга blade шаблона
     *
     * @param string $name
     * @param bool|null $withViewLog
     * @return ViewLogDto
     */
    public function createViewLog(string $name, ?bool $withViewLog): ViewLogDto
    {
        $dto = ViewLogDto::create(name: $name, withViewLog: $withViewLog);
        !Lh::config(ConfigEnum::ViewLog, 'store_on_start') ?: $dto->dispatch();

        return $dto;
    }


    /**
     * Обновляет dto для логирования рендеринга blade шаблона
     *
     * @return void
     */
    public function updateViewLog(ViewLogDto $dto, ?string &$render): void
    {
        $dto->render = $render;
        $dto->isUpdated = Lh::config(ConfigEnum::ViewLog, 'store_on_start');
        $dto->duration = $dto->getDuration();
        $dto->memory = $dto->getMemory();
        $dto->info = [
            ...($dto->info ?? []),
            'duration' => Hlp::timeSecondsToString(value: $dto->duration, withMilliseconds: true),
            'memory' => Hlp::sizeBytesToString($dto->memory),
            'render_length' => Hlp::stringLength($render),
        ];

        $dto->dispatch();
    }
}