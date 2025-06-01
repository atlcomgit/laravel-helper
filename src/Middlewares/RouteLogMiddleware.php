<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\LaravelHelper\Dto\RouteLogDto;
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

            $dto->dispatch();
        }

        return $next($request);
    }
}
