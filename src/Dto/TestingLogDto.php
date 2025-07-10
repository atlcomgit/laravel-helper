<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Carbon\Carbon;
use Throwable;

/**
 * Dto тестирования
 */
class TestingLogDto extends DefaultDto
{
    public ?ApplicationTypeEnum $type;
    public ?string $class;
    public ?bool $success;
    public ?Throwable $exception;
    public ?float $duration;
    public ?int $memory;

    public string $startTime;
    public int $startMemory;


    /**
     * @inheritDoc
     * @see parent::defaults()
     */
    protected function defaults(): array
    {
        return [
            'startTime' => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
        ];
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return float
     */
    public function getDuration(): float
    {
        return max(0, Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000);
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return int
     */
    public function getMemory(): int
    {
        return max(0, memory_get_usage() - $this->startMemory);
    }
}
