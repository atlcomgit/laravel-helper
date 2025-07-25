<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;

/**
 * Dto события кеширования http запроса
 */
class HttpCacheEventDto extends DefaultDto
{
    public EventTypeEnum $type;
    public ?array $tags;
    public ?string $key;
    public int|bool|null $ttl;
    public ?string $requestMethod;
    public ?string $requestUrl;
    public array|string|null $requestData = null;
    public int $responseCode = 0;
    public array $responseHeaders = [];
    public mixed $responseData;
}
