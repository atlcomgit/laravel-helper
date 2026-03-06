<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * Dto события блокировки ip адреса
 */
class IpBlockEventDto extends DefaultDto
{
    public string $ip;
    public string $reason;
    public string $source;
    public string $description;
    public bool   $isBlocked;
    public int    $blockedAt;
    public int    $expiresAt;
    public int    $ttl;
}
