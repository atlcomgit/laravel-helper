<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request as RequestIn;
use Illuminate\Http\Client\Request as RequestOut;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseIn;
use Illuminate\Http\Client\Response as ResponseOut;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dto лога http запроса
 */
class HttpLogDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public ?string $uuid;

    public int|string|null $userId;
    public ?string $name;
    public ?HttpLogTypeEnum $type;
    public ?HttpLogStatusEnum $status;
    public ?string $method;
    public ?string $ip;
    public string $url;
    public ?array $requestHeaders;
    public ?string $requestData;

    public ?int $responseCode;
    public ?string $responseMessage;
    public ?array $responseHeaders;
    public ?string $responseData;
    public ?string $cacheKey;
    public bool $isCached;
    public bool $isFromCache;
    public ?float $duration;
    public ?int $size;
    public ?array $info;

    public ?Exception $exception;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'userId' => user(returnOnlyId: true),
            'isCached' => false,
            'isFromCache' => false,
        ];
    }


    /**
     * Создает dto из http запроса
     *
     * @param string|null $uuid
     * @param RequestIn|RequestOut|null $request
     * @param array|null $info
     * @return static
     */
    public static function createByRequest(
        string|null $uuid,
        RequestIn|RequestOut|null $request = null,
        ?array $info = null,
    ): static {
        return static::create([
            'uuid' => $uuid,
            'status' => HttpLogStatusEnum::Process,

            ...match (true) {
                $request instanceof RequestIn => [
                    'name' => // $request->route()->getName() ?:
                        class_basename($request->route()?->getControllerClass())
                        . '::' . $request->route()?->getActionMethod(),
                    'type' => HttpLogTypeEnum::In,
                    'method' => Str::lower($request->getMethod()),
                    'ip' => ip(),
                    'url' => $request->getUri(),
                    'requestHeaders' => $request->headers->all(),
                    'requestData' => $request->getContent(),
                ],
                $request instanceof RequestOut => [
                    'name' => ($request->header(HttpLogService::HTTP_HEADER_NAME) ?? [])[0] ?? null,
                    'type' => HttpLogTypeEnum::Out,
                    'method' => Str::lower($request->method()),
                    'ip' => ip(),
                    'url' => $request->url(),
                    'requestHeaders' => $request->headers(),
                    'requestData' => $request->body(),
                    'cacheKey' => ($request->header(HttpLogService::HTTP_HEADER_CACHE_KEY) ?? [])[0] ?? null,
                    'isCached' => Hlp::castToBool(
                        ($request->header(HttpLogService::HTTP_HEADER_CACHE_SET) ?? [])[0] ?? null
                    ),
                    'isFromCache' => Hlp::castToBool(
                        ($request->header(HttpLogService::HTTP_HEADER_CACHE_GET) ?? [])[0] ?? null
                    ),
                ],

                default => [],
            },

            'info' => [
                ...($info ?? []),
                'request_data_size' => Hlp::sizeBytesToString(
                    Str::length(
                        match (true) {
                            $request instanceof RequestIn => $request->getContent(),
                            $request instanceof RequestOut => $request->body(),

                            default => '',
                        },
                    ),
                ),
            ],
        ]);
    }


    /**
     * Создает dto из http ответа
     *
     * @param string|null $uuid
     * @param RequestIn|RequestOut|null $request
     * @param ResponseIn|ResponseOut|StreamedResponse|BinaryFileResponse|null $response
     * @param array|null $attributes
     * @return static
     */
    public static function createByResponse(
        string|null $uuid,
        RequestIn|RequestOut|null $request = null,
        ResponseIn|ResponseOut|StreamedResponse|BinaryFileResponse|null $response = null,
        ?array $attributes = null,
    ): static {
        $startAt = match (true) {
            $response instanceof StreamedResponse => $attributes['startAt'] ?? null,
            $response instanceof BinaryFileResponse => $attributes['startAt'] ?? null,
            $response instanceof ResponseIn => $attributes['startAt'] ?? null,
            $response instanceof ResponseOut => ($request->header(HttpLogService::HTTP_HEADER_TIME) ?? [])[0]
            ?? null,

            default => null,
        };
        $duration = $startAt ? Carbon::createFromTimestampMs($startAt)->diffInMilliseconds() / 1000 : 0;
        $size = match (true) {
            $response instanceof StreamedResponse => Str::length($response->getContent()),
            $response instanceof BinaryFileResponse => $response->getFile()->getSize(),
            $response instanceof ResponseIn => Str::length($response->getContent()),
            $response instanceof ResponseOut => Str::length($response->body()),

            default => 0,
        };

        return ($dto = static::createByRequest($uuid, $request))
            ->merge([
                ...match (true) {
                    $response instanceof StreamedResponse => [
                        'responseCode' => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData' => '[' . $response::class . ']',
                        'status' => $response->getStatusCode() === ResponseIn::HTTP_OK
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof BinaryFileResponse => [
                        'responseCode' => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData' => '[' . $response::class . ', ' . $response->getFile()->getMimeType() . ']',
                        'status' => $response->getStatusCode() === ResponseIn::HTTP_OK
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof ResponseIn => [
                        'responseCode' => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData' => Hlp::castToString($response->getContent()),
                        'status' => $response->getStatusCode() === ResponseIn::HTTP_OK
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof ResponseOut => [
                        'responseCode' => $response->status(),
                        'responseMessage' => $response->reason(),
                        'responseHeaders' => $response->headers(),
                        'responseData' => Hlp::castToString($response->body()),
                        'status' => $response->successful()
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],

                    default => [
                        'responseData' => is_object($response) ? $response::class : $response,
                        'status' => HttpLogStatusEnum::Failed,
                    ],
                },

                'info' => [
                    ...($dto->info ?? []),
                    'duration' => Hlp::timeSecondsToString(value: $duration, withMilliseconds: true),
                    'response_data_size' => Hlp::sizeBytesToString($size),
                ],
                ...(isset($attributes['cacheKey']) ? ['cacheKey' => $attributes['cacheKey']] : []),
                ...(isset($attributes['isCached'])
                    ? [
                        'isCached' => $response->getStatusCode() === ResponseIn::HTTP_OK
                            ? Hlp::castToBool($attributes['isCached'] ?? false)
                            : false
                    ]
                    : []
                ),
                ...(isset($attributes['isFromCache'])
                    ? ['isFromCache' => Hlp::castToBool($attributes['isFromCache'] ?? false)]
                    : []
                ),
                'duration' => $duration ?? null,
                'size' => $size,
            ]);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::HttpLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? HttpLogJob::dispatchSync($this)
                : HttpLogJob::dispatch($this);
        }

        return $this;
    }
}
