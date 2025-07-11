<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Repositories\RouteLogRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Сервис логирования роутов
 */
class RouteLogService extends DefaultService
{
    public function __construct(private RouteLogRepository $routeLogRepository) {}


    /**
     * Возвращает список зарегистрированных роутов
     *
     * @return RouteCollectionInterface
     */
    public function getRoutes(): RouteCollectionInterface
    {
        return Route::getRoutes();
    }


    /**
     * Возвращает роут по запросу
     *
     * @param Request $request
     * @return string|null
     */
    public function getRouteByRequest(Request $request): ?\Illuminate\Routing\Route
    {
        return $this->getRoutes()->match($request);
    }


    /**
     * Логирование роута
     *
     * @param RouteLogDto $dto
     * @return void
     */
    public function log(RouteLogDto $dto): void
    {
        $this->routeLogRepository->incrementCount($dto);
    }


    /**
     * Очищает логи роутов
     *
     * @return int
     */
    public function cleanup(): int
    {
        if (!Lh::config(ConfigEnum::RouteLog, 'enabled')) {
            return 0;
        }

        $callback = function () {
            /** @var \Illuminate\Routing\Route[] $routes */
            $routes = $this->getRoutes();
            $count = 0;

            $this->routeLogRepository->setExistAll(false);

            foreach ($routes as $route) {
                foreach ($route->methods as $method) {
                    if (!in_array($method, ['HEAD'])) {
                        $dto = RouteLogDto::create(
                            method: $method,
                            uri: $route->uri,
                            controller: trim(
                                class_basename($route->getControllerClass()) . '::' . $route->getActionMethod(),
                                ':',
                            ),
                        );
                        $this->routeLogRepository->setExistOrCreate($dto);
                        $count++;
                    }
                }
            }

            $this->routeLogRepository->deleteNotExist();

            return $count;
        };

        return (!isTesting() && DB::transactionLevel() === 0) ? DB::transaction($callback) : $callback();
    }
}
