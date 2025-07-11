<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Dto\RouteLogDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
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
        ApplicationDto::create(type: ApplicationTypeEnum::Http, class: $request->route()?->getControllerClass());

        if (Lh::config(ConfigEnum::RouteLog, 'enabled')) {
            $dto = RouteLogDto::create(
                method: $request->method(),
                uri: $this->routeLogService->getRouteByRequest($request)?->uri ?? $request->getPathInfo(),
                controller: trim(
                    class_basename($request->route()?->getControllerClass())
                    . '::' . $request->route()?->getActionMethod(),
                    ':',
                ),
            );

            $dto->dispatch();
        }

        return $next($request);
    }
}
