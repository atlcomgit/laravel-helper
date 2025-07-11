<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Models\QueueLog;
use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;

/**
 * Dto лога очереди
 */
class QueueLogDto extends Dto
{
    public ?string $uuid;
    public int|string|null $userId;
    public string $jobId;
    public string $jobName;
    public string $name;
    public string $connection;
    public string $queue;
    public array $payload;
    public ?Carbon $delay;
    public int $attempts;
    public QueueLogStatusEnum $status;
    public ?string $exception;
    public ?float $duration;
    public ?int $memory;
    public ?array $info;

    public bool $withQueueLog;
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
            'userId' => user(returnOnlyId: true),
            'attempts' => 0,
            'status' => QueueLogStatusEnum::getDefault(),

            'withQueueLog' => false,
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
        return [
            ...QueueLog::getModelCasts(),

            'delay' => static fn ($v) => match (true) {
                $v instanceof DateTimeInterface => Carbon::parse($v),
                $v instanceof DateInterval => $v->format('Y-m-d H:i:s'),
                is_array($v) => $v,
                is_integer($v) => Carbon::parse($v),
                is_null($v) => null,

                default => $v,
            },
        ];
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     */
    protected function mappings(): array
    {
        return [
            'userId' => 'user_id',
            'jobId' => 'job_id',
            'jobName' => 'job_name',
        ];
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
        $this->onlyKeys(QueueLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->excludeKeys(['withQueueLog', 'isUpdated']);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch(): void
    {
        if (
            Lh::canDispatch($this)
            && (
                $this->withQueueLog === true
                || ($this->withQueueLog !== false && Lh::config(ConfigEnum::QueueLog, 'global'))
            )
        ) {
            (Lh::config(ConfigEnum::QueueLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? QueueLogJob::dispatchSync($this)
                : QueueLogJob::dispatch($this);
        }
    }
}
