<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\IpBlockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Посредник блокировки ip адресов
 */
class IpBlockMiddleware
{
    /**
     * Обрабатывает входящий запрос
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!Lh::config(ConfigEnum::IpBlock, 'enabled')) {
            return $next($request);
        }

        // Проверяем, не заблокирован ли уже IP-адрес клиента
        $service = app(IpBlockService::class);
        $ip = $service->resolveClientIp($request);

        if ($service->isAllowListedIp($ip)) {
            return $next($request);
        }

        if ($service->isBlockedIp($ip)) {
            return response('Access denied by ip block policy', $service->getResponseStatus());
        }

        // Регистрируем входящий запрос для последующего анализа и возможной блокировки
        $service->registerIncomingRequest($request);

        if ($service->isBlockedIp($ip)) {
            return response('Access denied by ip block policy', $service->getResponseStatus());
        }

        return $next($request);
    }


    /**
     * Обрабатывает завершение запроса
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!Lh::config(ConfigEnum::IpBlock, 'enabled')) {
            return;
        }

        $service = app(IpBlockService::class);

        if ($service->isAllowListedIp($service->resolveClientIp($request))) {
            return;
        }

        $service->registerRequestResponse($request, $response);
    }
}
