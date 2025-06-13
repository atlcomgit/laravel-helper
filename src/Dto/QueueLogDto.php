<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Jobs\QueueLogJob;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;

/**
 * Dto лога очереди
 */
class QueueLogDto extends Dto
{
    public ?string $uuid;
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
    public ?array $info;

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
            'attempts' => 0,
            'status' => QueueLogStatusEnum::getDefault(),
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
     */
    protected function mappings(): array
    {
        return [
            'jobId' => 'job_id',
            'jobName' => 'job_name',
        ];
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
        $this->onlyKeys(QueueLog::getModelKeys())
            ->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->excludeKeys(['isUpdated']);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch(): void
    {
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            isTesting()
                ? QueueLogJob::dispatchSync($this)
                : QueueLogJob::dispatch($this);
        }
    }
}
