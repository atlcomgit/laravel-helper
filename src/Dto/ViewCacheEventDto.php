<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\EventTypeEnum;

/**
 * Dto события кеширования рендеринга blade шаблона
 */
class ViewCacheEventDto extends DefaultDto
{
    public EventTypeEnum $type;
    public ?string $key;
    public ?string $view;
    public ?array $data;
    public ?array $mergeData;
    public ?array $ignoreData;
    public int|bool|null $ttl;
    public ?string $render;
}
