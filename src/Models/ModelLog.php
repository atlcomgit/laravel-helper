<?php

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\ModelLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;

/**
 * Модель: Лог модели
 * 
 * @see \Atlcom\LaravelHelper\Dto\ModelLogDto
 * @see ./database/migrations/2025_05_27_000001_create_model_logs_table.php
 * 
 * @property int $id
 * @property ?string $user_id
 * @property string $model_type
 * @property ?string $model_id
 * @property ModelLogTypeEnum $type
 * @property array $attributes
 * @property ?array $changes
 * @property \Carbon\Carbon $created_at
 * 
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Model $model
 * 
 * @method Relation|User user()
 * @method Relation|\Illuminate\Database\Eloquent\Model model()
 * @method static|Builder|ModelLog query()
 * @method static \Illuminate\Database\Eloquent\Factories\Factory|ModelLogFactory factory($count = null, $state = [])
 * @method static|Builder|static ofModel($model)
 * @mixin \Eloquent
 */
class ModelLog extends DefaultModel
{
    use DynamicTableModelTrait;


    protected ?bool $withModelLog = false;
    public $guarded = ['id'];
    public $timestamps = false;
    public $forceDeleting = true;
    protected $casts = [
        'user_id' => 'string',
        'model_type' => 'string',
        'model_id' => 'string',
        'type' => ModelLogTypeEnum::class,
        'attributes' => 'array',
        'changes' => 'array',
        'created_at' => 'datetime',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|ModelLogFactory
     */
    protected static function newFactory(): ModelLogFactory
    {
        return ModelLogFactory::new();
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
     * Отношение к модели
     *
     * @return Relation
     */
    public function model(): Relation
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }


    /**
     * Фильтр по модели
     *
     * @param Builder $query
     * @param Model|null $model
     * @return Builder
     */
    public function scopeOfModel(Builder $query, ?Model $model = null): Builder
    {
        return $query
            ->where('model_type', $model::class)
            ->where('model_id', $model->{$model->getKeyName()});
    }


    /**
     * Фильтр по классу модели
     *
     * @param Builder $query
     * @param class-string|null $modelType
     * @return Builder
     */
    public function scopeOfModelType(Builder $query, ?string $modelType = null): Builder
    {
        return $query->where('model_type', $modelType);
    }


    /**
     * Фильтр по типу лога
     *
     * @param Builder $query
     * @param ModelLogTypeEnum $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, ModelLogTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }
}