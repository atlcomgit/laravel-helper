<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\ViewLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;

/**
 * Модель: Лог query запроса
 * 
 * @see \Atlcom\LaravelHelper\Dto\QueryLogDto
 * @see ./database/migrations/2025_06_06_000001_create_query_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property ?string $user_id
 * @property string $name
 * @property ?array $data
 * @property ?array $merge_data
 * @property ?string $render
 * @property ?string $cache_key
 * @property bool $is_cached
 * @property bool $is_from_cache
 * @property ViewLogStatusEnum $status
 * @property ?float $duration
 * @property ?int $memory
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder ofName(string $name)
 * @method static|Builder ofStatus(ViewLogStatusEnum $status)
 * @mixin \Eloquent
 */
class ViewLog extends DefaultModel
{
    use DynamicTableModelTrait;


    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'string',
        'name' => 'string',
        'data' => 'array',
        'merge_data' => 'array',
        'render' => 'string',
        'cache_key' => 'string',
        'is_cached' => 'bool',
        'is_from_cache' => 'bool',
        'status' => ViewLogStatusEnum::class,
        'duration' => 'float',
        'memory' => 'integer',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|ViewLogFactory
     */
    protected static function newFactory(): ViewLogFactory
    {
        return ViewLogFactory::new();
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
     * Отношение к пользователю
     *
     * @return Relation
     */
    public function user(): Relation
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * Фильтр по status
     *
     * @param Builder $query
     * @param ViewLogStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, ViewLogStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
