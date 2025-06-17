<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Illuminate\Support\Str;

/**
 * Dto создания лога http запроса
 */
class HttpLogCreateDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public string $uuid;

    public int|string|null $userId;
    public ?string $name;
    public HttpLogTypeEnum $type;
    public HttpLogMethodEnum $method;
    public HttpLogStatusEnum $status;
    public ?string $ip;
    public string $url;
    public ?array $requestHeaders;
    public ?string $requestData;
    public ?string $requestHash;

    public ?int $responseCode;
    public ?string $responseMessage;
    public ?array $responseHeaders;
    public ?string $responseData;
    public ?array $info;


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
            'uuid' => uuid(),
            'status' => HttpLogStatusEnum::getDefault(),
        ];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return HttpLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     *
     * @return array
     */
    // #[Override()]
    protected function mappings(): array
    {
        return [
            'uuid' => 'request_headers.' . HttpLogService::HTTP_HEADER_UUID . '.0',
            'name' => 'request_headers.' . HttpLogService::HTTP_HEADER_NAME . '.0',
        ];
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilled(array $array): void
    {
        $this->ip = $this->ip ? Str::substr($this->ip, 0, 255) : $this->ip;
        $this->url = Str::substr($this->url, 0, 2048);
        $this->requestHash ??= $this->onlyKeys('userId', 'url', 'requestData')->getHash();
        !$this->responseMessage ?: $this->responseMessage = Str::substr($this->responseMessage, 0, 255);
        !$this->responseData ?: $this->responseData = mb_convert_encoding($this->responseData, 'utf-8');
        $this->name = match ($this->name) {
            null, '', HttpLogHeaderEnum::Unknown->value => Hlp::urlParse($this->url)['host'],

            default => $this->name,
        };
    }


    /**
     * @inheritDoc
     * @see parent::onSerializing()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(HttpLog::getModelKeys())
            ->onlyNotNull()
            ->includeArray([
                ...($this->responseData ? ['try_count' => 1] : []),
            ]);
    }
}
