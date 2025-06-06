<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Helper;
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
use Symfony\Component\HttpFoundation\Response as ResponseIn;
use Illuminate\Http\Client\Response as ResponseOut;

/**
 * Dto лога http запроса
 */
class HttpLogDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public ?string $uuid;

    public ?int $userId;
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
    public ?array $info;

    public ?Exception $exception;


    /**
     * @override
     * Возвращает массив значений по умолчанию
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'userId' => user()?->id,
        ];
    }


    /**
     * Создает dto из http запроса
     *
     * @param string|null $uuid
     * @param RequestIn|RequestOut|null|null $request
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
                'request_data_size' => Helper::sizeBytesToString(
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
     * @param RequestIn|RequestOut|null|null $request
     * @param ResponseIn|ResponseOut|null|null $response
     * @param array|null $info
     * @return static
     */
    public static function createByResponse(
        string|null $uuid,
        RequestIn|RequestOut|null $request = null,
        ResponseIn|ResponseOut|null $response = null,
        ?array $info = null,
    ): static {
        return ($dto = static::createByRequest($uuid, $request))
            ->merge([
                ...match (true) {
                    $response instanceof ResponseIn => [
                        'responseCode' => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData' => $response->getContent(),
                        'status' => $response->getStatusCode() === ResponseIn::HTTP_OK
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof ResponseOut => [
                        'responseCode' => $response->status(),
                        'responseMessage' => $response->reason(),
                        'responseHeaders' => $response->headers(),
                        'responseData' => $response->body(),
                        'status' => $response->successful()
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],

                    default => [
                        'status' => HttpLogStatusEnum::Failed,
                    ],
                },

                'info' => [
                    ...($dto->info ?? []),
                    ...($info ?? []),
                    ...(($startAt = ($request->header(HttpLogService::HTTP_HEADER_TIME) ?? [])[0] ?? null)
                        ? [
                            'duration' => Helper::timeSecondsToString(
                                (int)Carbon::createFromTimestampMs($startAt)->diffInMilliseconds() / 1000
                            ),
                        ]
                        : []
                    ),
                    'response_data_size' => Helper::sizeBytesToString(
                        Str::length(
                            match (true) {
                                $response instanceof ResponseIn => $response->getContent(),
                                $response instanceof ResponseOut => $response->body(),

                                default => '',
                            },
                        ),
                    ),
                ],
            ]);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        $type = $this->type->value;

        if (
            !config("laravel-helper.http_log.{$type}.enabled")
            || app(LaravelHelperService::class)
                ->checkExclude("laravel-helper.http_log.{$type}.exclude", $this->serializeKeys(true)->toArray())
        ) {
            return;
        }

        isTesting()
            ? HttpLogJob::dispatchSync($this)
            : HttpLogJob::dispatch($this);
    }
}
