<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\ConsoleLogJob;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;

/**
 * Dto лога консольной команды
 * @method ConsoleLogDto output(?string $value)
 * @method ConsoleLogDto info(?array $value)
 */
class ConsoleLogDto extends Dto
{
    public ?string $uuid;
    public string $name;
    public string $command;
    public string $cli;
    public ?string $output;
    public ?int $result;
    public ConsoleLogStatusEnum $status;
    public ?string $exception;
    public ?array $info;

    public ?bool $withConsoleLog;
    public int $storeInterval;
    public string $startTime;
    public int $startMemory;
    public Carbon $storedAt;
    public bool $isUpdated;


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
            'cli' => implode(' ', $_SERVER['argv'] ?? []),
            'status' => ConsoleLogStatusEnum::getDefault(),

            'withConsoleLog' => false,
            'storeInterval' => config('laravel-helper.console_log.store_interval_seconds', 10),
            'startTime' => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
            'storedAt' => now(),
            'isUpdated' => false,
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
        return ConsoleLog::getModelCasts();
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
        $this->onlyKeys(ConsoleLog::getModelKeys())
            ->onlyNotNull()
            ->excludeKeys(['withConsoleLog', 'storeInterval', 'startTime', 'startMemory', 'storedAt', 'isUpdated']);
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return string
     */
    public function getDuration(): string
    {
        return Hlp::timeSecondsToString(
            value: Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000,
            withMilliseconds: true,
        );
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return string
     */
    public function getMemory(): string
    {
        return Hlp::sizeBytesToString(memory_get_usage() - $this->startMemory);
    }


    /**
     * Подготавливает dto для сохранения лога
     *
     * @param bool $isForce
     * @return void
     */
    public function store(bool $isForce = true): void
    {
        if ($isForce || $this->storedAt->diffInSeconds() >= $this->storeInterval) {
            $this->isUpdated = (bool)$this->uuid;
            $this->uuid ??= uuid();
            $this->storedAt = now();
            $this->dispatch();

            is_null($this->output) ?: $this->output = '';
        }
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this) && $this->withConsoleLog) {
            isTesting()
                ? ConsoleLogJob::dispatchSync($this)
                : ConsoleLogJob::dispatch($this);
        }
    }
}
