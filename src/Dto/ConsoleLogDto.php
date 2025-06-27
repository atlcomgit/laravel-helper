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
use Illuminate\Support\Facades\Schema;

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
    public ?float $duration;
    public ?int $memory;
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
     * Подготавливает dto для сохранения лога
     *
     * @param bool $isForce
     * @return void
     */
    public function store(bool $isForce = true): void
    {
        static $tableExists = null;

        if (is_null($tableExists)) {
            $connection = config('laravel-helper.console_log.connection');
            $tableExists = Schema::connection($connection)->hasTable(config('laravel-helper.console_log.table'));

            if (!$tableExists) {
                return;
            }
        }

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
        if (
            app(LaravelHelperService::class)->canDispatch($this)
            && (
                $this->withConsoleLog === true
                || ($this->withConsoleLog !== false && config('laravel-helper.console_log.global'))
            )
        ) {
            config('laravel-helper.console_log.queue_dispatch_sync')
                ? ConsoleLogJob::dispatchSync($this)
                : ConsoleLogJob::dispatch($this);
        }
    }
}
