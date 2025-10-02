<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto настройки лога http запроса
 */
class HttpLogConfigDto extends DefaultDto
{
    public ?bool $enabled = null;

    // Отключает лог

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


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyFilled();
    }
}
