<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\HttpLogConfigDto;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Посредник логирования входящих http запросов
 */
class HttpLogMiddleware
{
    private static ?string $uuid = null;
    private static ?string $startAt = null;


    public function handle(Request $request, Closure $next, ?string $httpLogConfigDtoJson = null)
    {
        $dto = null;
        if (Lh::config(ConfigEnum::HttpLog, 'enabled') && Lh::config(ConfigEnum::HttpLog, 'in.enabled')) {
            $config = Hlp::cryptDecode($httpLogConfigDtoJson, 'log') ?: HttpLogConfigDto::create();

            $method = $request->getMethod();
            $url = $request->getUri();
            $headers = json($request->headers->all(), Hlp::jsonFlags());
            $query = json($request->query->all(), Hlp::jsonFlags());
            $data = $request->getContent();

            $config->enabled = ($config->enabled ?? true)
                && !Hlp::stringSearchAny($method, $config->disableCacheMethods ?? [])
                && !Hlp::stringSearchAny($url, $config->disableCacheUrls ?? [])
                && !Hlp::stringSearchAny($headers, $config->disableCacheHeaders ?? [])
                && !Hlp::stringSearchAny($query, $config->disableCacheQueries ?? [])
                && !Hlp::stringSearchAny($data, $config->disableCacheData ?? []);

            if ($config->enabled ?? true) {
                static::$startAt = (string)now()->getTimestampMs();
                static::$uuid = uuid();
                $dto = HttpLogDto::createByRequest(static::$uuid, $request);
            }
        }

        !(static::$uuid && $dto && !Lh::config(ConfigEnum::HttpLog, 'only_response'))
            ?: $dto->dispatch();

        return $next($request);
    }


    public function terminate(Request $request, Response $response): void
    {
        !static::$uuid
            ?: HttpLogDto::createByResponse(static::$uuid, $request, $response, [
                'startAt' => static::$startAt,
                'cacheKey' => HttpCacheMiddleware::$cacheKey,
                'isCached' => HttpCacheMiddleware::$isCached,
                'isFromCache' => HttpCacheMiddleware::$isFromCache,
            ])->dispatch()
        ;
    }
}
