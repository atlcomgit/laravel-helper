<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;

/**
 * Модель: Лог.Http запрос
 *
 * @see HttpLogCreateDto
 * @see HttpLogUpdateDto
 * @see ./database/migrations/2025_05_26_000001_create_http_logs_table.php
 * @property int $id
 * @property string $uuid
 * @property ?string $user_id
 * @property ?string $name
 * @property HttpLogTypeEnum $type
 * @property HttpLogMethodEnum $method
 * @property HttpLogStatusEnum $status
 * @property string $url
 * @property ?array $request_headers
 * @property ?string $request_data
 * @property string $request_hash
 * @property ?int $response_code
 * @property ?string $response_message
 * @property ?array $response_headers
 * @property ?string $response_data
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property int|null $try_count Количество попыток запроса
 * @property-read ?User $user
 * @property-read array $config
 * @property-read array $files
 * @property-read \Illuminate\Support\Collection $media
 * @property-read string|null $morph_name
 * @method static \Illuminate\Database\Eloquent\Builder|HttpLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HttpLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DefaultModel ofPage(int $page, int $limit, int $count)
 * @method static \Illuminate\Database\Eloquent\Builder|DefaultModel ofSort(array|string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|HttpLog query()
 * @property mixed|null $info Информация о запросе
 * @mixin \Eloquent
 */
class HttpLog extends DefaultModel
{
    protected $table = 'http_logs';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'integer',
        'name' => 'string',
        'type' => HttpLogTypeEnum::class,
        'method' => HttpLogMethodEnum::class,
        'status' => HttpLogStatusEnum::class,
        'url' => 'string',
        'request_headers' => 'array',
        'request_data' => 'string',
        'request_hash' => 'string',
        'response_code' => 'integer',
        'response_message' => 'string',
        'response_headers' => 'array',
        'response_data' => 'string',
        // 'try_count' => 'integer', - не нужно!
        'info' => 'array',
    ];


    /*
     * ATTRIBUTES
     */


    /*
     * MUTATORS
     */


    /*
     * RELATIONS
     */


    public function user(): Relation
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /*
     * SCOPES
     */
}
