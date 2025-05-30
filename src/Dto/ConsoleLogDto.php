<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Helper;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\ConsoleLogJob;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Carbon\Carbon;

/**
 * Dto лога консольной команды
 * @method ConsoleLogDto output(?string $value)
 * @method ConsoleLogDto info(?array $value)
 */
class ConsoleLogDto extends Dto
{
    public ?string $uuid;
    public string $command;
    public string $name;
    public string $cli;
    public ?string $output;
    public ?int $result;
    public ConsoleLogStatusEnum $status;
    public ?string $exception;
    public ?array $info;

    public int $storeInterval;
    public string $startTime;
    public int $startMemory;
    public Carbon $storedAt;
    public bool $isUpdated;


    /**
     * @override
     * Возвращает массив значений по умолчанию
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'cli' => implode(' ', $_SERVER['argv']),
            'status' => ConsoleLogStatusEnum::getDefault(),

            'storeInterval' => config('laravel-helper.console_log.store_interval_seconds', 10),
            'startTime' => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
            'storedAt' => now(),
            'isUpdated' => false,
        ];
    }


    /**
     * Возвращает массив преобразований типов
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return ConsoleLog::getModelCasts();
    }


    /**
     * Метод вызывается до преобразования dto в массив
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(ConsoleLog::getModelKeys())
            ->onlyNotNull()
            ->excludeKeys(['storeInterval', 'startTime', 'startMemory', 'storedAt', 'isUpdated']);
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return string
     */
    public function getDuration(): string
    {
        return Helper::timeSecondsToString(
            (int)Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000
        );
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return string
     */
    public function getMemory(): string
    {
        return Helper::sizeBytesToString(memory_get_usage() - $this->startMemory);
    }


    /**
     * Отправляет данные в очередь для сохранения лога
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

            isTesting()
                ? ConsoleLogJob::dispatchSync($this)
                : ConsoleLogJob::dispatch($this);


            is_null($this->output) ?: $this->output = '';
        }
    }
}
