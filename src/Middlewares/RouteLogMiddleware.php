<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Jobs\RouteLogJob;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Atlcom\LaravelHelper\Services\RouteLogService;
use Closure;
use Illuminate\Http\Request;

/**
 * Посредник логирования зарегистрированных роутов
 */
final class RouteLogMiddleware
{
    public function __construct(
        private RouteLogService $routeLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    /**
     * Обработчик middleware
     *
     * @param Request $request
     * @param Closure $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('laravel-helper.route_log.enabled')) {
            $dto = RouteLogDto::create(
                method: $request->method(),
                uri: $this->routeLogService->getRouteByRequest($request)?->uri ?? $request->getPathInfo(),
                controller: class_basename($request->route()?->getControllerClass())
                . '::' . $request->route()?->getActionMethod()
            );

            $this->dispatch($dto);
        }

        return $next($request);
    }


    /**
     * Отправляет данные в очередь
     *
     * @param RouteLogDto $dto
     * @return void
     */
    public function dispatch(RouteLogDto $dto): void
    {
        if ($this->laravelHelperService->checkExclude('laravel-helper.route_log.exclude', $dto->toArray())) {
            return;
        }

        isTesting()
            ? RouteLogJob::dispatchSync($dto)
            : RouteLogJob::dispatch($dto);
    }
}
