<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;

/**
 * Dto события кеширования query запроса
 */
class QueryCacheEventDto extends DefaultDto
{
    public EventTypeEnum $type;
    public ?array $tags;
    public ?string $key;
    public int|bool|null $ttl;
    public mixed $data;
}
