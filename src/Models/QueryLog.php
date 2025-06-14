<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\QueryLogFactory;
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
 * @property ?string $name
 * @property string $query
 * @property ?string $cache_key
 * @property bool $is_cached
 * @property bool $is_from_cache
 * @property QueryLogStatusEnum $status
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder ofStatus(QueryLogStatusEnum $status)
 * @mixin \Eloquent
 */
class QueryLog extends DefaultModel
{
    use DynamicTableModelTrait;


    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'string',
        'name' => 'string',
        'query' => 'string',
        'cache_key' => 'string',
        'is_cached' => 'bool',
        'is_from_cache' => 'bool',
        'status' => QueryLogStatusEnum::class,
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|QueryLogFactory
     */
    protected static function newFactory(): QueryLogFactory
    {
        return QueryLogFactory::new();
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
     * Фильтр по status
     *
     * @param Builder $query
     * @param QueryLogStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, QueryLogStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
