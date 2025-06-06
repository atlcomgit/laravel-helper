<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\QueueLogFactory;
use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель: Лог задач
 * 
 * @see \Atlcom\LaravelHelper\Dto\QueueLogDto
 * @see ./database/migrations/2025_05_30_000002_create_queue_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property string $job_id
 * @property string $job_name
 * @property string $name
 * @property string $connection
 * @property string $queue
 * @property string $payload
 * @property DateTimeInterface|DateInterval|array|int|null $delay
 * @property int $attempts
 * @property QueueLogStatusEnum $status
 * @property ?string $exception
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder ofJobId(string $jobId)
 * @method static|Builder ofJobName(Builder $query, string $jobName)
 * @method static|Builder ofName(string $name)
 * @method static|Builder ofAttempts(int $attempts)
 * @method static|Builder ofStatus(QueueLogStatusEnum $status)
 * @mixin \Eloquent
 */
class QueueLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public bool $logEnabled = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'job_id' => 'string',
        'job_name' => 'string',
        'name' => 'string',
        'connection' => 'string',
        'queue' => 'string',
        'payload' => 'array',
        'delay' => 'datetime',
        'attempts' => 'integer',
        'status' => QueueLogStatusEnum::class,
        'exception' => 'string',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|QueueLogFactory
     */
    protected static function newFactory(): QueueLogFactory
    {
        return QueueLogFactory::new();
    }


    /*
     * ATTRIBUTES
     */


    /*
     * MUTATORS
     */


    /*
     * RELATIONS
     */


    /*
     * SCOPES
     */


    /**
     * Фильтр по uuid
     *
     * @param Builder $query
     * @param string $uuid
     * @return Builder
     */
    public function scopeOfUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }


    /**
     * Фильтр по job_id
     *
     * @param Builder $query
     * @param string $jobId
     * @return Builder
     */
    public function scopeOfJobId(Builder $query, string $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }


    /**
     * Фильтр по class
     *
     * @param Builder $query
     * @param string $jobName
     * @return Builder
     */
    public function scopeOfJobName(Builder $query, string $jobName): Builder
    {
        return $query->where('job_name', $jobName);
    }


    /**
     * Фильтр по name
     *
     * @param Builder $query
     * @param string $name
     * @return Builder
     */
    public function scopeOfName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }


    /**
     * Фильтр по connection
     *
     * @param Builder $query
     * @param string $connection
     * @return Builder
     */
    public function scopeOfConnection(Builder $query, string $connection): Builder
    {
        return $query->where('connection', $connection);
    }


    /**
     * Фильтр по queue
     *
     * @param Builder $query
     * @param string $queue
     * @return Builder
     */
    public function scopeOfQueue(Builder $query, string $queue): Builder
    {
        return $query->where('queue', $queue);
    }


    /**
     * Фильтр по attempts
     *
     * @param Builder $query
     * @param ?int $attempts
     * @return Builder
     */
    public function scopeOfAttempts(Builder $query, ?int $attempts): Builder
    {
        return $query->where('attempts', $attempts);
    }


    /**
     * Фильтр по status
     *
     * @param Builder $query
     * @param QueueLogStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, QueueLogStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
