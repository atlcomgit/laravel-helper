<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Models\RouteLog;
use Throwable;

/**
 * Репозиторий логирования роутов
 */
class RouteLogRepository extends DefaultRepository
{
    public function __construct(private ?string $routeLogClass = null)
    {
        $this->routeLogClass ??= lhConfig(ConfigEnum::RouteLog, 'model') ?? RouteLog::class;
    }


    /**
     * Создает новую модель
     *
     * @param RouteLogDto $dto
     * @return RouteLog
     */
    public function new(RouteLogDto $dto): RouteLog
    {
        return $this->withoutTelescope(fn () => (new $this->routeLogClass($dto->toArray()))
            ->setConnection(lhConfig(ConfigEnum::RouteLog, 'connection'))
            ->setTable(lhConfig(ConfigEnum::RouteLog, 'table')));
    }


    /**
     * Устанавливает флаг exist если роут существует или создает новую запись лога роута
     *
     * @param RouteLogDto $dto
     * @return void
     */
    public function setExistOrCreate(RouteLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var RouteLog $routeLog */
            $routeLog = $this->routeLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofMethod($dto->method)
                ->ofUri($dto->uri)
                ->first()
                ?? $this->new($dto);
            $routeLog->controller = $dto->controller;
            $routeLog->exist = true;
            $routeLog->save();
        });
    }


    /**
     * Обнуляет счетчик лога роута
     *
     * @param RouteLogDto $dto
     * @return void
     */
    public function setCountZero(RouteLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            /** @var RouteLog $routeLog */
            $routeLog = $this->routeLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofMethod($dto->method)
                ->ofUri($dto->uri)
                ->first()
                ?? $this->new($dto);
            $routeLog->count = 0;
            $routeLog->save();
        });
    }


    /**
     * Обновляет или создает лог роута
     *
     * @param RouteLogDto $dto
     * @return void
     */
    public function incrementCount(RouteLogDto $dto): void
    {
        $this->withoutTelescope(function () use ($dto) {
            try {
                /** @var RouteLog $routeLog */
                $routeLog = $this->routeLogClass::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->ofMethod($dto->method)
                    ->ofUri($dto->uri)
                    ->first()
                    ?? $this->new($dto);
                $routeLog->controller = $dto->controller;
                $routeLog->count++;
                $routeLog->save();
            } catch (Throwable $exception) {
            }
        });
    }


    /**
     * Устанавливает флаг exist для всех записей роутов
     *
     * @param bool $value
     * @return void
     */
    public function setExistAll(bool $value): void
    {
        $this->withoutTelescope(function () use ($value) {
            $this->routeLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->update(['exist' => $value]);
        });
    }


    /**
     * Удаляет записи роутов с флагом exist=false (не существующие роуты)
     *
     * @return void
     */
    public function deleteNotExist(): void
    {
        $this->withoutTelescope(function () {
            $this->routeLogClass::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofExist(false)
                ->delete();
        });
    }
}
