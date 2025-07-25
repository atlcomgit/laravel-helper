<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\HttpCacheDto;
use Atlcom\LaravelHelper\Dto\HttpCacheEventDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;
use Atlcom\LaravelHelper\Enums\HttpCacheMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Events\HttpCacheEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
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
    protected array $exclude = [];


    public function __construct()
    {
        $this->cacheService = app(CacheService::class);
        $this->exclude = Lh::config(ConfigEnum::HttpCache, 'exclude') ?? [];
    }


    /**
     * Регистрирует макросы для Http запроса PendingRequest
     *
     * @param PendingRequest $request
     * @param int|string|bool|null|null $ttl
     * @return PendingRequest
     */
    public function setMacro(PendingRequest $request, int|string|bool|null $ttl = null): PendingRequest
    {
        $request->macro(
            'getWithCache',
            fn (string $url, array|string|null $query = null)
            => app(HttpCacheService::class)->sendWithCache($request, HttpCacheMethodEnum::Get, $url, $query, $ttl)
        );
        $request->macro(
            'postWithCache',
            fn (string $url, $data = [])
            => app(HttpCacheService::class)->sendWithCache($request, HttpCacheMethodEnum::Post, $url, $data, $ttl)
        );

        return new class ($request, $ttl) extends PendingRequest {
            public function __construct(
                protected PendingRequest $pendingRequest,
                protected int|string|bool|null $ttl = null,
            ) {}


            /**
             * GET исходящий запрос
             * @override
             * @see parent::get()
             *
             * @param string $url
             * @param array|null $query
             * @return ResponseIn|ResponseOut|BinaryFileResponse|StreamedResponse|null
             */
            // #[Override()]
            public function get(string $url, $query = null)
            {
                return app(HttpCacheService::class)
                    ->sendWithCache($this->pendingRequest, HttpCacheMethodEnum::Get, $url, $query, $this->ttl);
            }


            /**
             * POST исходящий запрос
             * @override
             * @see parent::post()
             *
             * @param string $url
             * @param array $data
             * @return ResponseIn|ResponseOut|BinaryFileResponse|StreamedResponse|null
             */
            // #[Override()]
            public function post(string $url, $data = [])
            {
                return app(HttpCacheService::class)
                    ->sendWithCache($this->pendingRequest, HttpCacheMethodEnum::Post, $url, $data, $this->ttl);
            }


        };
    }


    /**
     * Отправляет http запрос с использованием кеша
     *
     * @param PendingRequest $request
     * @param HttpCacheMethodEnum $method
     * @param string $url
     * @param array|string|null|null $data
     * @param int|string|bool|null|null $ttl
     * @param mixed 
     * @return ResponseIn|ResponseOut|BinaryFileResponse|StreamedResponse|null
     */
    public function sendWithCache(
        PendingRequest $request,
        HttpCacheMethodEnum $method,
        string $url,
        array|string|null $data = null,
        int|string|bool|null $ttl = null,
    ): ResponseIn|ResponseOut|BinaryFileResponse|StreamedResponse|null {
        $httpCacheDto = $this->createHttpDto($request, $method->value, $url, $data, $ttl);

        if ($httpCacheDto->key) {
            $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_KEY, $httpCacheDto->key);
            $httpCacheService = app(HttpCacheService::class);

            if ($httpCacheService->hasHttpCache($httpCacheDto)) {
                $request->replaceHeaders(
                    Hlp::arrayDeleteKeys(
                        HttpLogService::getLogHeaders(HttpLogHeaderEnum::Unknown),
                        [HttpLogService::HTTP_HEADER_NAME],
                    ),
                );
                $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_GET, true);

                $httpCacheService->getHttpCache($httpCacheDto);

                $stream = Utils::streamFor($data);
                $psrRequest = new PsrRequest($method->value, $url, $request->getOptions()['headers'] ?? [], $stream);
                $clientRequest = new RequestOut($psrRequest);

                event(new ResponseReceived($clientRequest, $httpCacheDto->response));

                return $httpCacheDto->response;
            }

            $request->withHeader(HttpLogService::HTTP_HEADER_CACHE_SET, true);
        }

        $httpCacheDto->response = match ($method) {
            HttpCacheMethodEnum::Get => $request->get($url, $data),
            HttpCacheMethodEnum::Post => $request->post($url, $data),

            default => null,
        };

        $isSuccessful = match (true) {
            $httpCacheDto->response instanceof ResponseIn => $httpCacheDto->response->isSuccessful(),
            $httpCacheDto->response instanceof ResponseOut => $httpCacheDto->response->successful(),
            $httpCacheDto->response instanceof BinaryFileResponse => $httpCacheDto->response->isSuccessful(),
            $httpCacheDto->response instanceof StreamedResponse => $httpCacheDto->response->isSuccessful(),

            default => false,
        };
        if ($httpCacheDto->key && $isSuccessful) {
            $httpCacheService->setHttpCache($httpCacheDto);
        }

        return $httpCacheDto->response;

    }


    /**
     * Возвращает dto из http запроса
     *
     * @param RequestIn|RequestOut|PendingRequest $request
     * @param string $method
     * @param string $url
     * @param array|string|null|null $data
     * @param int|string|bool|null|null $ttl
     * @param mixed 
     * @return HttpCacheDto
     */
    public function createHttpDto(
        RequestIn|RequestOut|PendingRequest $request,
        string $method,
        string $url,
        array|string|null $data = null,
        int|string|bool|null $ttl = null,
    ): HttpCacheDto {
        $ttl = $this->cacheService->getCacheTtl($ttl);
        $key = $this->getCacheKey($request, $method, $url, $data, $ttl);

        $dto = HttpCacheDto::create(
            key: $ttl === false ? null : $key,
            ttl: $ttl,
            requestMethod: $method,
            requestUrl: $url,
            requestHeaders: match (true) {
                $request instanceof RequestIn => $request->headers->all(),
                $request instanceof RequestOut => $request->headers(),
                $request instanceof PendingRequest => $request->getOptions()['headers'] ?? [],

                default => [],
            },
            requestData: $data,
        );

        if ($this->exclude && !app(LaravelHelperService::class)->canDispatch($dto)) {
            $dto->key = null;
        }

        return $dto;
    }


    /**
     * Возвращает ключ кеша
     *
     * @param RequestIn|RequestOut|PendingRequest $request
     * @return string
     */
    public function getCacheKey(
        RequestIn|RequestOut|PendingRequest $request,
        string $method,
        string $url,
        array|string|null $data = null,
        int|bool|null $ttl = null,
    ): string {
        $ttl = match (true) {
            is_null($ttl) || $ttl === 0 => 'ttl_not_set',
            is_integer($ttl) => "ttl_{$ttl}",
            is_bool($ttl) => "ttl_default",

            default => '',
        };

        return "{$ttl}_" . Hlp::hashXxh128(
            Str::lower($method)
            . $url
            . json(
                Hlp::arrayDeleteKeys(
                    match (true) {
                        $request instanceof RequestIn => $request->headers->all(),
                        $request instanceof RequestOut => $request->headers(),
                        $request instanceof PendingRequest => $request->getOptions()['headers'] ?? [],

                        default => [],
                    },
                    [
                        HttpLogService::HTTP_HEADER_UUID,
                        HttpLogService::HTTP_HEADER_NAME,
                        HttpLogService::HTTP_HEADER_TIME,
                        HttpLogService::HTTP_HEADER_CACHE_KEY,
                        HttpLogService::HTTP_HEADER_CACHE_SET,
                        HttpLogService::HTTP_HEADER_CACHE_GET,
                    ],
                ),
            )
            . (is_array($data) ? json($data) : ($data ?? ''))
        );
    }


    /**
     * Проверяет наличие ключа http запроса в кеше 
     *
     * @param HttpCacheDto $dto
     * @return bool
     */
    public function hasHttpCache(HttpCacheDto $dto): bool
    {
        if (!$dto->key) {
            return false;
        }

        return $this->cacheService->hasCache(ConfigEnum::HttpCache, [], $dto->key);
    }


    /**
     * Сохраняет ключ http запроса в кеше
     *
     * @param HttpCacheDto $dto
     * @return void
     */
    public function setHttpCache(HttpCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                $response = [
                    'status' => $dto->response->getStatusCode(),
                    'headers' => match (true) {
                        $dto->response instanceof StreamedResponse => $dto->response->headers->all(),
                        $dto->response instanceof BinaryFileResponse => $dto->response->headers->all(),
                        $dto->response instanceof ResponseIn => $dto->response->headers->all(),
                        $dto->response instanceof ResponseOut => $dto->response->getHeaders(),

                        default => [],
                    },
                    'data' => match (true) {
                        $dto->response instanceof StreamedResponse => '[' . $dto->response::class . ']',
                        $dto->response instanceof BinaryFileResponse
                        => '[' . $dto->response::class . ', ' . $dto->response->getFile()->getMimeType() . ']',
                        $dto->response instanceof ResponseIn => Hlp::castToString($dto->response->getContent()),
                        $dto->response instanceof ResponseOut => Hlp::castToString($dto->response->body()),

                        default => null,
                    },
                ];
                ($dto->ttl === false)
                    ?: $this->cacheService
                        ->setCache(ConfigEnum::HttpCache, $dto->tags, $dto->key, $response, $dto->ttl);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::SetHttpCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            ttl: $dto->ttl,
                            requestMethod: $dto->requestMethod,
                            requestUrl: $dto->requestUrl,
                            requestData: $dto->requestData,
                            responseCode: $response['status'],
                            responseHeaders: $response['headers'],
                            responseData: $response['data'],
                        ),
                    ),
                );
            }
        );
    }


    /**
     * Возвращает ключ http запроса из кеша
     *
     * @param HttpCacheDto $dto
     * @return void
     */
    public function getHttpCache(HttpCacheDto $dto): void
    {
        $this->withoutTelescope(
            function () use (&$dto) {
                if (!$dto->key) {
                    return;
                }

                $response = $this->cacheService->getCache(ConfigEnum::HttpCache, $dto->tags, $dto->key, null);
                $stream = Utils::streamFor($response['data']);
                $psrResponse = new PsrResponse($response['status'], $response['headers'], $stream);
                $dto->response = new ResponseOut($psrResponse);

                event(
                    new HttpCacheEvent(
                        HttpCacheEventDto::create(
                            type: EventTypeEnum::GetHttpCache,
                            tags: $dto->tags,
                            key: $dto->key,
                            requestMethod: $dto->requestMethod,
                            requestUrl: $dto->requestUrl,
                            requestData: $dto->requestData,
                            responseCode: $response['status'],
                            responseHeaders: $response['headers'],
                            responseData: $response['data'],
                        ),
                    ),
                );
            }
        );
    }


    /**
     * Удаляет ключи http запроса из кеша по тегам
     *
     * @param array $tags
     * @return void
     */
    public function flushHttpCache(array $tags = []): void
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


    /**
     * Удаляет все ключи http запроса из кеша
     *
     * @return void
     */
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
