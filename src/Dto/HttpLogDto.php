<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
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
            $response instanceof ResponseIn => $attributes['startAt'] ?? null,
            $response instanceof ResponseOut => ($request->header(HttpLogService::HTTP_HEADER_TIME) ?? [])[0]
            ?? null,

            default => null,
        };
        $duration = $startAt ? Carbon::createFromTimestampMs($startAt)->diffInMilliseconds() / 1000 : 0;
        $size = Str::length(
            match (true) {
                $response instanceof ResponseIn => $response->getContent(),
                $response instanceof ResponseOut => $response->body(),

                default => '',
            },
        );

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
                        'responseData' => $response::class,
                        'status' => HttpLogStatusEnum::Failed,
                    ],
                },

                'info' => [
                    ...($dto->info ?? []),
                    'duration' => Hlp::timeSecondsToString(value: $duration, withMilliseconds: true),
                    'response_data_size' => Hlp::sizeBytesToString($size),
                ],
                'duration' => $duration ?? null,
                'size' => $size,

            ]);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            (lhConfig(ConfigEnum::HttpLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? HttpLogJob::dispatchSync($this)
                : HttpLogJob::dispatch($this);
        }
    }
}
