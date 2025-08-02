<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\RouteLogFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель: Лог роута
 * 
 * @see \Atlcom\LaravelHelper\Dto\RouteLogDto
 * @see ./database/migrations/2025_06_01_000006_create_route_logs_table.php
 *
 * @property int $id
 * @property string $method
 * @property string $uri
 * @property ?string $controller
 * @property int $count
 * @property bool $exist
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @method static|Builder ofMethod(string $method)
 * @method static|Builder ofUri(string $uri)
 * @method static|Builder ofExist(bool $exist)
 * @mixin \Eloquent
 */
class RouteLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public const COMMENT = 'Лог роутов';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'method' => 'string',
        'uri' => 'string',
        'controller' => 'string',
        'count' => 'integer',
        'exist' => 'boolean',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|RouteLogFactory
     */
    protected static function newFactory(): RouteLogFactory
    {
        return RouteLogFactory::new();
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
     * Фильтр по uri
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
     * Фильтр по uri
     *
     * @param Builder $query
     * @param string $uri
     * @return Builder
     */
    public function scopeOfUri(Builder $query, string $uri): Builder
    {
        return $query->where('uri', $uri);
    }


    /**
     * Фильтр по exist
     *
     * @param Builder $query
     * @param bool $exist
     * @return Builder
     */
    public function scopeOfExist(Builder $query, bool $exist): Builder
    {
        return $query->where('exist', $exist);
    }
}
