<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @internal
 * Репозиторий логирования роутов
 */
class RouteLogRepository extends DefaultRepository
{
    public function __construct(
        /** @var RouteLog */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::RouteLog, 'model') ?? RouteLog::class;
    }


    /**
     * Создает новую модель
     *
     * @param RouteLogDto $dto
     * @return RouteLog
     */
    public function new(RouteLogDto $dto): RouteLog
    {
        return $this->withoutTelescope(
            fn () => (new $this->model($dto->toArray()))
                ->setConnection(Lh::getConnection(ConfigEnum::RouteLog))
                ->setTable(Lh::getTable(ConfigEnum::RouteLog))
        );
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
            $routeLog = $this->model::query()
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
            $routeLog = $this->model::query()
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
                $this->model::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->ofMethod($dto->method)
                    ->ofUri($dto->uri)
                    ->update([
                        'controller' => $dto->controller,
                        'count' => DB::raw('count + 1'),
                    ]);
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
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->update(['exist' => $value])
        );
    }


    /**
     * Удаляет записи роутов с флагом exist=false (не существующие роуты)
     *
     * @return void
     */
    public function deleteNotExist(): void
    {
        $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofExist(false)
                ->delete()
        );
    }
}
