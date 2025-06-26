<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Carbon\Carbon;

/**
 * Dto приложения
 */
class ApplicationDto extends DefaultDto
{
    public ?string $uuid;
    public ?ApplicationTypeEnum $type;
    public ?string $class;

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
     * @inheritDoc
     * @see parent::onFilled()
     */
    protected function onFilled(array $array): void
    {
        $this->store();
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return float
     */
    public function getDuration(): float
    {
        return Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000;
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


    /**
     * Сохраняет во временный кеш dto
     *
     * @return static
     */
    public function store(): static
    {
        Hlp::cacheRuntimeSet('LaravelHelper.Application', $this);

        return $this;
    }


    /**
     * Загружает из временного кеша dto
     *
     * @return static|null
     */
    public static function restore(): ?static
    {
        return Hlp::cacheRuntimeGet('LaravelHelper.Application');
    }
}
