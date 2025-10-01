<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\HttpCacheConfigDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\HttpCacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Посредник кеширования входящих http запросов
 */
class HttpCacheMiddleware
{
    public static bool $cacheEnabled = false;
    public static ?string $cacheKey = null;
    public static bool $isCached = false;
    public static bool $isFromCache = false;


    public function handle(Request $request, Closure $next, ?string $httpCacheConfigDtoJson = null)
    {
        if (static::$cacheEnabled = Lh::config(ConfigEnum::HttpCache, 'enabled')) {
            $config = HttpCacheConfigDto::create(
                Hlp::regexpValidateJson($httpCacheConfigDtoJson) ? $httpCacheConfigDtoJson : '{}'
            );

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
                $httpCacheService = app(HttpCacheService::class);
                $httpCacheDto = $httpCacheService->createHttpDto(
                    request: $request,
                    method: $method,
                    url: $url,
                    data: $data,
                    config: $config,
                );

                if (static::$cacheKey = $httpCacheDto->key) {
                    if ($httpCacheService->hasHttpCache($httpCacheDto)) {
                        $httpCacheService->getHttpCache($httpCacheDto);
                        static::$isFromCache = true;

                        return $httpCacheDto->response;
                    }

                    /** @var Response $response */
                    $response = $next($request);

                    // Сохраняем только успешные ответы
                    if ($httpCacheDto->key && $response->isSuccessful()) {
                        $httpCacheDto->response = $response;
                        $httpCacheService->setHttpCache($httpCacheDto);
                        static::$isCached = true;
                    }

                    return $response;
                }
            }
        }

        return $next($request);
    }
}
