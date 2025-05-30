<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\ConsoleLogFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель: Лог консольной команды
 * 
 * @see \Atlcom\LaravelHelper\Dto\ConsoleLogDto
 * @see ./database/migrations/2025_05_30_000001_create_console_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property string $class
 * @property string $name
 * @property string $command
 * @property ?string $output
 * @property ?int $result
 * @property ConsoleLogStatusEnum $status
 * @property ?string $exception
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static Builder ofClass(string $class)
 * @method static Builder ofName(string $name)
 * @method static Builder ofResult(string $result)
 * @method static Builder ofStatus(ConsoleLogStatusEnum $status)
 * @mixin \Eloquent
 */
class ConsoleLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public bool $logEnabled = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'class' => 'string',
        'name' => 'string',
        'command' => 'string',
        'output' => 'string',
        'status' => ConsoleLogStatusEnum::class,
        'result' => 'integer',
        'exception' => 'string',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|ConsoleLogFactory
     */
    protected static function newFactory(): ConsoleLogFactory
    {
        return ConsoleLogFactory::new();
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
    public function scopeOfUUid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }


    /**
     * Фильтр по class
     *
     * @param Builder $query
     * @param string $class
     * @return Builder
     */
    public function scopeOfClass(Builder $query, string $class): Builder
    {
        return $query->where('class', $class);
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
     * Фильтр по name
     *
     * @param Builder $query
     * @param ?int $result
     * @return Builder
     */
    public function scopeOfResult(Builder $query, ?int $result): Builder
    {
        return $query->where('result', $result);
    }


    /**
     * Фильтр по status
     *
     * @param Builder $query
     * @param ConsoleLogStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, ConsoleLogStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
