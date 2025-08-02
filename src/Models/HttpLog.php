<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\HttpLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;

/**
 * Модель: Лог http запроса
 *
 * @see \Atlcom\LaravelHelper\Dto\HttpLogDto
 * @see \Atlcom\LaravelHelper\Dto\HttpLogCreateDto
 * @see \Atlcom\LaravelHelper\Dto\HttpLogUpdateDto
 * @see \Atlcom\LaravelHelper\Dto\HttpLogFailedDto
 * @see ./database/migrations/2025_06_01_000002_create_http_logs_table.php
 * 
 * @property int $id
 * @property string $uuid
 * @property ?string $user_id
 * @property ?string $name
 * @property HttpLogTypeEnum $type
 * @property HttpLogMethodEnum $method
 * @property HttpLogStatusEnum $status
 * @property ?string $ip
 * @property string $url
 * @property ?array $request_headers
 * @property ?string $request_data
 * @property string $request_hash
 * @property ?int $response_code
 * @property ?string $response_message
 * @property ?array $response_headers
 * @property ?string $response_data
 * @property ?string $cache_key
 * @property bool $is_cached
 * @property bool $is_from_cache
 * @property int|null $try_count
 * @property ?float $duration
 * @property ?int $size
 * @property array|null $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * 
 * @property-read ?User $user
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|HttpLog query()
 * @method static \Illuminate\Database\Eloquent\Factories\Factory|HttpLogFactory factory($count = null, $state = [])
 * @method static|Builder|static ofIp($ip)
 * @method static|Builder|static ofResponseCode($code)
 * @mixin \Eloquent
 */
class HttpLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public const COMMENT = 'Лог http запросов';

    protected ?bool $withModelLog = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'integer',
        'name' => 'string',
        'type' => HttpLogTypeEnum::class,
        'method' => HttpLogMethodEnum::class,
        'status' => HttpLogStatusEnum::class,
        'ip' => 'string',
        'url' => 'string',
        'request_headers' => 'array',
        'request_data' => 'string',
        'request_hash' => 'string',
        'response_code' => 'integer',
        'response_message' => 'string',
        'response_headers' => 'array',
        'response_data' => 'string',
        'cache_key' => 'string',
        'is_cached' => 'bool',
        'is_from_cache' => 'bool',
        // 'try_count' => 'integer', - не нужно!
        'duration' => 'float',
        'size' => 'integer',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|HttpLogFactory
     */
    protected static function newFactory(): HttpLogFactory
    {
        return HttpLogFactory::new();
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
     * Фильтр по ip
     *
     * @param Builder $query
     * @param string|null $ip
     * @return Builder
     */
    public function scopeOfIp(Builder $query, ?string $ip): Builder
    {
        return $query->where('ip', $ip);
    }


    /**
     * Фильтр по ip
     *
     * @param Builder $query
     * @param string|null $ip
     * @return Builder
     */
    public function scopeOfResponseCode(Builder $query, ?string $code): Builder
    {
        return $query->where('response_code', $code);
    }
}
