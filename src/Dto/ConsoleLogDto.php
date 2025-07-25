<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\ConsoleLogJob;
use Atlcom\LaravelHelper\Models\ConsoleLog;
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
    public ?string $exception;
    public ConsoleLogStatusEnum $status;
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
            'storeInterval' => Lh::config(ConfigEnum::ConsoleLog, 'store_interval_seconds', 10),
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
            ->excludeKeys([
                'withConsoleLog',
                'storeInterval',
                'startTime',
                'startMemory',
                'storedAt',
                'isUpdated',
            ]);
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
            $tableExists = Schema::connection(Lh::getConnection(ConfigEnum::ConsoleLog))
                ->hasTable(Lh::getTable(ConfigEnum::ConsoleLog));

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
            Lh::canDispatch($this)
            && (
                $this->withConsoleLog === true
                || ($this->withConsoleLog !== false && Lh::config(ConfigEnum::ConsoleLog, 'global'))
            )
        ) {
            (Lh::config(ConfigEnum::ConsoleLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? ConsoleLogJob::dispatchSync($this)
                : ConsoleLogJob::dispatch($this);
        }
    }
}
