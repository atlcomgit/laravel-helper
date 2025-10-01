<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto настройки кеша http запроса
 */
class HttpCacheConfigDto extends DefaultDto
{
    public ?bool $enabled = null;

    // Время жизни кеша
    public int|string|bool|null $ttl = null;

    // Отключает кеш

    /** @var string[] */
    public ?array $disableCacheMethods = null;
    /** @var string[] */
    public ?array $disableCacheUrls = null;
    /** @var string[] */
    public ?array $disableCacheHeaders = null;
    /** @var string[] */
    public ?array $disableCacheQueries = null;
    /** @var string[] */
    public ?array $disableCacheData = null;

    // Исключает из формирования хеша

    /** @var string[] */
    public ?array $ignoreHashMethods = null;
    /** @var string[] */
    public ?array $ignoreHashUrls = null;
    /** @var string[] */
    public ?array $ignoreHashHeaders = null;
    /** @var string[] */
    public ?array $ignoreHashData = null;
}
