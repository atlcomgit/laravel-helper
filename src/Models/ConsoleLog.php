<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Atlcom\LaravelHelper\Database\Factories\ConsoleLogFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель: Лог консольной команды
 * 
 * @see \Atlcom\LaravelHelper\Dto\ConsoleLogDto
 * @see ./database/migrations/2025_06_01_000001_create_console_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $command
 * @property string $cli
 * @property ?string $output
 * @property ?int $result
 * @property ?string $exception
 * @property ConsoleLogStatusEnum $status
 * @property ?float $duration
 * @property ?int $memory
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder ofCommand(string $command)
 * @method static|Builder ofName(string $name)s
 * @method static|Builder ofResult(int $result)
 * @method static|Builder ofStatus(ConsoleLogStatusEnum $status)
 * @mixin \Eloquent
 */
class ConsoleLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public const COMMENT = 'Лог консольных команд';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'name' => 'string',
        'command' => 'string',
        'cli' => 'string',
        'output' => 'string',
        'result' => 'integer',
        'exception' => 'string',
        'status' => ConsoleLogStatusEnum::class,
        'duration' => 'float',
        'memory' => 'integer',
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


    /** ATTRIBUTES */


    /** MUTATORS */


    /** RELATIONS */


    /** SCOPES */


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
     * Фильтр по command
     *
     * @param Builder $query
     * @param string $command
     * @return Builder
     */
    public function scopeOfCommand(Builder $query, string $command): Builder
    {
        return $query->where('command', $command);
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
