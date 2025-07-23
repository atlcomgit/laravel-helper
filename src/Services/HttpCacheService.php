<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\HttpCacheDto;
use Atlcom\LaravelHelper\Dto\HttpCacheEventDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;
use Atlcom\LaravelHelper\Events\HttpCacheEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request as RequestIn;
use Illuminate\Http\Client\Request as RequestOut;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseIn;
use Illuminate\Http\Client\Response as ResponseOut;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Сервис кеширования http запросов
 */
class HttpCacheService extends DefaultService
{
    protected CacheService $cacheService;
    protected string $driver = '';
    protected array $exclude = [];


    public function __construct()
    {
        $this->cacheService = app(CacheService::class);
        $this->driver = Lh::config(ConfigEnum::HttpCache, 'driver') ?: config('cache.default');
        $this->exclude = Lh::config(ConfigEnum::HttpCache, 'exclude') ?? [];
    }


    //?!? phpdoc
    public function setMacro(PendingRequest $request, int|string|bool|null $ttl = null): void
    {
        $request->macro(
            'get',
            fn (string $url, array|string|null $query = null)
            => app(HttpCacheService::class)->sendRequest($request, $ttl, 'GET', $url, $query)
        );
        $request->macro(
            'post',
            fn (string $url, $data = [])
            => app(HttpCacheService::class)->sendRequest($request, $ttl, 'POST', $url, $data)
        );
    }


    public function sendRequest(
        PendingRequest $request,
        string $method,
        int|string|bool|null $ttl = null,
        string $url,
        array|string|null $data = null,
    ): ResponseIn|ResponseOut|BinaryFileResponse|StreamedResponse|null {
        $httpCacheDto = $this->createHttpDto($request, $ttl, $data);

        if ($httpCacheDto->key) {
            $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_KEY, $httpCacheDto->key);
            $httpCacheService = app(HttpCacheService::class);

            if ($httpCacheService->hasHttpCache($httpCacheDto)) {
                $httpCacheService->getHttpCache($httpCacheDto);
                $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_GET, true);

                event(new ResponseReceived($request->toPsrRequest(), $httpCacheDto->response));

                return $httpCacheDto->response;
            }
        }

        $httpCacheDto->response = match ($method) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),

            default => null,
        };

        if ($httpCacheDto->key && $httpCacheDto->response->isSuccessful()) {
            $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_SET, true);
            $httpCacheService->setHttpCache($httpCacheDto);
        }

        return $httpCacheDto->response;

    }


    public function createHttpDto(
        RequestIn|RequestOut|PendingRequest $request,
        int|string|bool|null $ttl = null,
        array|string|null $data = null,
    ): HttpCacheDto {
        $ttl = $this->cacheService->getCacheTtl($ttl);

        return match (true) {
            $ttl === false => HttpCacheDto::create(
                key: null,
                ttl: $ttl,
            ),
            $request instanceof RequestIn => HttpCacheDto::create(
                key: $this->getCacheKey($request, $ttl),
                ttl: $ttl,
                requestHeaders: $request->headers->all(),
                requestData: $data ?? $request->getContent(),
            ),
            $request instanceof RequestOut => HttpCacheDto::create(
                key: $this->getCacheKey($request, $ttl),
                ttl: $ttl,
                requestHeaders: $request->headers(),
                requestData: $data ?? $request->body(),
            ),
            $request instanceof PendingRequest => HttpCacheDto::create(
                key: $this->getCacheKey($request, $ttl),
                ttl: $ttl,
                requestHeaders: $request->toPsrRequest()->getHeaders(),
                requestData: $data ?? $request->toPsrRequest()->getBody()->getContents(),
            ),

            default => HttpCacheDto::create(),
        };
    }


    /**
     * Возвращает ключ кеша
     *
     * @param RequestIn|RequestOut|PendingRequest $request
     * @return string
     */
    public function getCacheKey(RequestIn|RequestOut|PendingRequest $request, int|bool|null $ttl = null): string
    {
        $ttl = match (true) {
            is_null($ttl) || $ttl === 0 => 'ttl_not_set',
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",

            default => '',
        };

        return "{$ttl}_" . Hlp::hashXxh128(
            match (true) {
                $request instanceof RequestIn => Str::lower($request->getMethod())
                . $request->getUri()
                . json_encode($request->headers->all(), Hlp::jsonFlags())
                . $request->getContent(),
                $request instanceof RequestOut => Str::lower($request->method())
                . $request->url()
                . json_encode($request->headers(), Hlp::jsonFlags())
                . $request->body(),
                $request instanceof PendingRequest => Str::lower($request->toPsrRequest()->getMethod())
                . $request->toPsrRequest()->getUri()
                . json_encode($request->toPsrRequest()->getHeaders(), Hlp::jsonFlags())
                . $request->toPsrRequest()->getBody()->getContents(),

                default => null,
            },
        );
    }


    public function hasHttpCache(HttpCacheDto $dto): bool
    {
        if (!$dto->key) {
            return false;
        }

        return $this->cacheService->hasCache(ConfigEnum::HttpCache, [], $dto->key);
    }


    public function setHttpCache(HttpCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                ($dto->ttl === false)
                    ?: $this->cacheService
                        ->setCache(ConfigEnum::HttpCache, $dto->tags, $dto->key, $dto->response, $dto->ttl);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::SetHttpCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            ttl: $dto->ttl,
                            responseCode: $dto->response->getStatusCode(),
                            responseHeaders: match (true) {
                                $dto->response instanceof StreamedResponse => $dto->response->headers->all(),
                                $dto->response instanceof BinaryFileResponse => $dto->response->headers->all(),
                                $dto->response instanceof ResponseIn => $dto->response->headers->all(),
                                $dto->response instanceof ResponseOut => $dto->response->getHeaders(),
                            },
                            responseData: match (true) {
                                $dto->response instanceof StreamedResponse => '[' . $dto->response::class . ']',
                                $dto->response instanceof BinaryFileResponse => '[' . $dto->response::class . ', ' . $dto->response->getFile()->getMimeType() . ']',
                                $dto->response instanceof ResponseIn => Hlp::castToString($dto->response->getContent()),
                                $dto->response instanceof ResponseOut => Hlp::castToString($dto->response->body()),
                            },
                        ),
                    ),
                );
            }
        );
    }


    public function getHttpCache(HttpCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                $dto->response = $this->cacheService->getCache(ConfigEnum::HttpCache, $dto->tags, $dto->key, null);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::GetHttpCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            responseCode: $dto->response->getStatusCode(),
                            responseHeaders: match (true) {
                                $dto->response instanceof StreamedResponse => $dto->response->headers->all(),
                                $dto->response instanceof BinaryFileResponse => $dto->response->headers->all(),
                                $dto->response instanceof ResponseIn => $dto->response->headers->all(),
                                $dto->response instanceof ResponseOut => $dto->response->getHeaders(),
                            },
                            responseData: match (true) {
                                $dto->response instanceof StreamedResponse => '[' . $dto->response::class . ']',
                                $dto->response instanceof BinaryFileResponse => '[' . $dto->response::class . ', ' . $dto->response->getFile()->getMimeType() . ']',
                                $dto->response instanceof ResponseIn => Hlp::castToString($dto->response->getContent()),
                                $dto->response instanceof ResponseOut => Hlp::castToString($dto->response->body()),
                            },
                        ),
                    ),
                );
            }
        );
    }


    public function flushHttpCache(array $tags = [])
    {
        $this->withoutTelescope(
            function () use (&$tags) {
                $this->cacheService->flushCache(ConfigEnum::HttpCache, $tags);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::FlushHttpCache,
                            tags: $tags,
                        ),
                    ),
                );
            }
        );
    }


    public function flushHttpCacheAll()
    {
        $this->withoutTelescope(
            function () {
                $this->cacheService->flushCache(ConfigEnum::HttpCache, $tags = [ConfigEnum::HttpCache->value]);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::FlushHttpCache,
                            tags: $tags,
                        ),
                    ),
                );
            }
        );
    }
}
