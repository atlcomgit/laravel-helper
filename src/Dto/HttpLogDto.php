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
 * @internal
 * Dto лога http запроса
 * @see \Atlcom\LaravelHelper\Models\HttpLog
 */
class HttpLogDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;
    /** Размер тела запроса и ответа в логе по умолчанию в байтах, если в конфиге нет data_max_length */
    public const DATA_MAX_LENGTH = 1048576;

    public ?string $uuid;

    public int|string|null    $userId;
    public ?string            $name;
    public ?HttpLogTypeEnum   $type;
    public ?HttpLogStatusEnum $status;
    public ?string            $method;
    public ?string            $ip;
    public string             $url;
    public ?array             $requestHeaders;
    public ?string            $requestData;

    public ?int    $responseCode;
    public ?string $responseMessage;
    public ?array  $responseHeaders;
    public ?string $responseData;
    public ?string $cacheKey;
    public bool    $isCached;
    public bool    $isFromCache;
    public ?float  $duration;
    public ?int    $size;
    public ?array  $info;

    public ?Exception $exception;

    /** @var array<string, array{excluded: bool, hash: ?string}> Решения по полным телам и отпечатки их префиксов. */
    private array $truncatedDataExclusions = [];


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
            'userId'      => user(returnOnlyId: true),
            'isCached'    => false,
            'isFromCache' => false,
        ];
    }


    /**
     * Создает dto из http запроса
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyPreservesExclusion()
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
        // Исходное тело нужно для размера и исключений; в DTO сохраняется только ограниченный фрагмент.
        $requestData = match (true) {
            $request instanceof RequestIn => $request->getContent(),
            $request instanceof RequestOut => $request->body(),

            default => null,
        };
        $limitedRequestData = static::limitData($requestData);

        return static::create([
            'uuid'   => $uuid,
            'status' => HttpLogStatusEnum::Process,

            ...match (true) {
                $request instanceof RequestIn => [
                    'name'           => // $request->route()->getName() ?:
                        class_basename($request->route()?->getControllerClass() ?? '')
                        . '::' . $request->route()?->getActionMethod(),
                    'type'           => HttpLogTypeEnum::In,
                    'method'         => Str::lower($request->getMethod()),
                    'ip'             => ip(),
                    'url'            => $request->getUri(),
                    'requestHeaders' => $request->headers->all(),
                    'requestData'    => $limitedRequestData,
                ],
                $request instanceof RequestOut => [
                    'url'            => $url = $request->url(),
                    'name'           => (($request->header(HttpLogService::HTTP_HEADER_NAME) ?? [])[0] ?? null)
                        ?: Hlp::urlParse($url)['host'],
                    'type'           => HttpLogTypeEnum::Out,
                    'method'         => Str::lower($request->method()),
                    'ip'             => ip(),
                    'requestHeaders' => $request->headers(),
                    'requestData'    => $limitedRequestData,
                    'cacheKey'       => ($request->header(HttpLogService::HTTP_HEADER_CACHE_KEY) ?? [])[0] ?? null,
                    'isCached'       => Hlp::castToBool(
                        ($request->header(HttpLogService::HTTP_HEADER_CACHE_SET) ?? [])[0] ?? null
                    ),
                    'isFromCache'    => Hlp::castToBool(
                        ($request->header(HttpLogService::HTTP_HEADER_CACHE_GET) ?? [])[0] ?? null
                    ),
                ],

                default => [],
            },

            'info'   => [
                ...($info ?? []),
                'request_data_size' => Hlp::sizeBytesToString(Str::length($requestData ?? '')),
                ...($limitedRequestData !== $requestData ? ['request_data_truncated' => true] : []),
            ],
        ])->rememberDataExclusion('requestData', $requestData);
    }


    /**
     * Создает dto из http ответа
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyPreservesExclusion()
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
        // Файл ответа может быть удалён сразу после отправки (deleteFileAfterSend): тогда размер и тип из заголовков
        $file = $response instanceof BinaryFileResponse ? $response->getFile() : null;
        $isFileExists = $file?->isFile() ?? false;
        $size = match (true) {
            $response instanceof StreamedResponse => Str::length($response->getContent()),
            $response instanceof BinaryFileResponse => $isFileExists
                ? $file->getSize()
                : (int)$response->headers->get('Content-Length', '0'),
            $response instanceof ResponseIn => Str::length($response->getContent()),
            $response instanceof ResponseOut => Str::length($response->body()),

            default => 0,
        };
        // Проверка исключений использует исходное представление ответа, но не сохраняет его в DTO.
        $responseData = match (true) {
            $response instanceof StreamedResponse => '[' . $response::class . ']',
            $response instanceof BinaryFileResponse => '[' . $response::class . ', '
                . ($isFileExists ? $file->getMimeType() : $response->headers->get('Content-Type')) . ']',
            $response instanceof ResponseIn => Hlp::castToString($response->getContent()),
            $response instanceof ResponseOut => Hlp::castToString($response->body()),

            default => is_object($response) ? $response::class : $response,
        };
        $limitedResponseData = static::limitData($responseData);

        return ($dto = static::createByRequest($uuid, $request))
            ->merge([
                ...match (true) {
                    $response instanceof StreamedResponse => [
                        'responseCode'    => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData'    => $limitedResponseData,
                        'status'          => static::isSuccessfulInboundStatusCode($response->getStatusCode())
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof BinaryFileResponse => [
                        'responseCode'    => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData'    => $limitedResponseData,
                        'status'          => static::isSuccessfulInboundStatusCode($response->getStatusCode())
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof ResponseIn => [
                        'responseCode'    => $response->getStatusCode(),
                        'responseMessage' => $response::$statusTexts[$response->getStatusCode()],
                        'responseHeaders' => $response->headers->all(),
                        'responseData'    => $limitedResponseData,
                        'status'          => static::isSuccessfulInboundStatusCode($response->getStatusCode())
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],
                    $response instanceof ResponseOut => [
                        'responseCode'    => $response->status(),
                        'responseMessage' => $response->reason(),
                        'responseHeaders' => $response->headers(),
                        'responseData'    => $limitedResponseData,
                        'status'          => $response->successful()
                            ? HttpLogStatusEnum::Success
                            : HttpLogStatusEnum::Failed
                    ],

                    default => [
                        'responseData' => $limitedResponseData,
                        'status'       => HttpLogStatusEnum::Failed,
                    ],
                },

                'info'     => [
                    ...($dto->info ?? []),
                    'duration'           => Hlp::timeSecondsToString(value: $duration, withMilliseconds: true),
                    'response_data_size' => Hlp::sizeBytesToString($size),
                    ...($limitedResponseData !== $responseData ? ['response_data_truncated' => true] : []),
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
                'size'     => $size,
            ])
            ->rememberDataExclusion('responseData', $responseData);
    }


    /**
     * Проверяет исключения по полным усечённым телам и актуальным значениям остальных полей.
     * Повторная проверка префикса запрещена: он может совпасть с шаблоном, не совпадающим с полным телом.
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedPrefixDoesNotCreateExclusion()
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyKeepsExclusionsForUpdatedMetadata()
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::untruncatedBodyKeepsExistingExclusion()
     *
     * @return bool
     */
    public function notFoundConfigExclude(): bool
    {
        $this->forgetChangedDataExclusions();

        return !in_array(true, array_column($this->truncatedDataExclusions, 'excluded'), true)
            && Lh::notFoundConfigExclude(
                'laravel-helper.http_log.' . $this->type->value . '.exclude',
                $this->excludeKeys(array_keys($this->truncatedDataExclusions)),
            );
    }


    /**
     * Запоминает результат фильтрации и отпечаток префикса, не удерживая полное тело в DTO или очереди.
     * Штатный проверяющий метод сохраняет формат ключей и семантику существующих шаблонов исключений.
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyPreservesExclusion()
     *
     * @param string $property Имя свойства requestData или responseData.
     * @param string|null $data Полное тело до применения лимита.
     * @return static
     */
    private function rememberDataExclusion(string $property, ?string $data): static
    {
        if (
            $data === $this->{$property}
            || $this->type === null
            || !Lh::config(ConfigEnum::HttpLog, 'enabled')
            || !Lh::config(ConfigEnum::HttpLog, $this->type->value . '.enabled')
        ) {
            return $this;
        }

        $this->truncatedDataExclusions[$property] = [
            'excluded' => !Lh::notFoundConfigExclude(
                'laravel-helper.http_log.' . $this->type->value . '.exclude',
                $this->onlyKeys($property)->includeArray([$property => $data]),
            ),
            'hash' => $this->{$property} === null ? null : hash('sha256', $this->{$property}),
        ];

        return $this;
    }


    /**
     * Сбрасывает решения при замене публичных тел через merge, заполнение или прямое присвоение.
     * Отпечаток проверяется только для усечённых полей и не удерживает прежнее тело в памяти.
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyRechecksReplacement()
     *
     * @return void
     */
    private function forgetChangedDataExclusions(): void
    {
        foreach ($this->truncatedDataExclusions as $property => $exclusion) {
            if ($exclusion['hash'] !== ($this->{$property} === null ? null : hash('sha256', $this->{$property}))) {
                unset($this->truncatedDataExclusions[$property]);
            }
        }
    }


    /**
     * Проверяет, что код входящего HTTP-ответа относится к успешному диапазону.
     *
     * @param int $statusCode
     * @return bool
     */
    private static function isSuccessfulInboundStatusCode(int $statusCode): bool
    {
        return $statusCode >= ResponseIn::HTTP_OK
            && $statusCode < ResponseIn::HTTP_MULTIPLE_CHOICES;
    }


    /**
     * Обрезает тело запроса или ответа до размера из настройки data_max_length, не разрывая символы UTF-8
     *
     * Ограничение выполняется до дальнейшей обработки данных: иначе тело ответа в десятки мегабайт копируется
     * при сериализации и записи лога и исчерпывает память процесса. Обрезанное тело больше не является JSON,
     * поэтому при сериализации и записи сохраняется, как любой текст не в формате JSON, первыми 2048 символами.
     * Если в опубликованном конфиге приложения нет ключа data_max_length, действует DATA_MAX_LENGTH.
     *
     * @param string|null $data
     * @return string|null
     */
    private static function limitData(?string $data): ?string
    {
        $maxLength = (int)(Lh::config(ConfigEnum::HttpLog, 'data_max_length') ?? static::DATA_MAX_LENGTH);

        return $maxLength > 0 && $data !== null && strlen($data) > $maxLength
            ? mb_strcut($data, 0, $maxLength, 'UTF-8')
            : $data;
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::HttpLog, 'queue_dispatch_sync') ?? (isLocal() || isDev() || isTesting()))
                ? HttpLogJob::dispatchSync($this)
                : HttpLogJob::dispatch($this);
        }

        return $this;
    }


    /**
     * Очищает данные очереди и сохраняет решения по исходным телам при штатном усечении текста.
     * @see parent::__serialize()
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyPreservesExclusion()
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::largeResponseKeepsOnlyBoundedPayload()
     *
     * @return array
     */
    public function __serialize(): array
    {
        $this->forgetChangedDataExclusions();
        $this->requestData = Hlp::stringTruncateBetweenQuotes($this->removeMultipartBinary($this->requestData), 2048);
        $this->responseData = Hlp::stringTruncateBetweenQuotes($this->removeMultipartBinary($this->responseData), 2048);

        // Штатная очистка для очереди не является заменой тела и сохраняет решение по исходным данным.
        foreach (array_keys($this->truncatedDataExclusions) as $property) {
            $this->truncatedDataExclusions[$property]['hash'] = $this->{$property} === null
                ? null
                : hash('sha256', $this->{$property});
        }

        return parent::__serialize();
    }


    /**
     * Восстанавливает решения по полным телам, поскольку родитель заполняет только доступные ему свойства.
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedBodyPreservesExclusion()
     * @see \Atlcom\LaravelHelper\Tests\Unit\HttpLogDtoTest::truncatedPrefixDoesNotCreateExclusion()
     *
     * @param array $data Сериализованные поля DTO с приватным состоянием исключений.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->truncatedDataExclusions = $data["\0" . self::class . "\0truncatedDataExclusions"] ?? [];
    }


    /**
     * Удаляет бинарные данные
     *
     * @param string|null $data
     * @return string|null
     */
    private function removeMultipartBinary(?string $data): ?string
    {
        if (!$data || Hlp::regexpValidateJson($data)) {
            return $data;
        }

        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

        return Hlp::stringCopy(
            $data
            ? mb_convert_encoding(preg_replace('/[\x00-\x08\x0A-\x0C\x0E-\x1F]/u', '', $data), 'UTF-8', 'auto')
            : $data,
            0,
            2048,
        );
    }
}
