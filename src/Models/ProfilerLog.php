<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Atlcom\LaravelHelper\Database\Factories\ProfilerLogFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель: Лог консольной команды
 * 
 * @see \Atlcom\LaravelHelper\Dto\ProfilerLogDto
 * @see ./database/migrations/2025_06_01_000008_create_profiler_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property ?string $class
 * @property ?string $method
 * @property bool $is_static
 * @property ?array $arguments
 * @property ProfilerLogStatusEnum $status
 * @property ?string $result
 * @property ?string $exception
 * @property int $count
 * @property ?float $duration
 * @property ?int $memory
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder ofClass(string $class)
 * @method static|Builder ofMethod(string $method)
 * @method static|Builder ofStatus(ProfilerLogStatusEnum $status)
 * @mixin \Eloquent
 */
class ProfilerLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public const COMMENT = 'Лог профилирования метода класса';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'class' => 'string',
        'method' => 'string',
        'is_static' => 'boolean',
        'arguments' => 'array',
        'status' => ProfilerLogStatusEnum::class,
        'result' => 'string',
        'exception' => 'string',
        'count' => 'integer',
        'duration' => 'float',
        'memory' => 'integer',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|ProfilerLogFactory
     */
    protected static function newFactory(): ProfilerLogFactory
    {
        return ProfilerLogFactory::new();
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
     * Фильтр по method
     *
     * @param Builder $query
     * @param string $method
     * @return Builder
     */
    public function scopeOfMethod(Builder $query, string $method): Builder
    {
        return $query->where('method', $method);
    }


    /**
     * Фильтр по status
     *
     * @param Builder $query
     * @param ProfilerLogStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, ProfilerLogStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
