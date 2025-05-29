<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Трейт для подключения к модели только для чтения
 */
trait ReadonlyModelTrait
{
    // protected $connection = 'pgsql_readonly';
    protected $guarded = ['*'];
    protected $fillable = [];


    // #[Override()]
    public function save(array $options = [])
    {
        throw new Exception('Cannot save read-only model');
    }


    // #[Override()]
    public static function create(array $attributes = [])
    {
        throw new Exception('Cannot update read-only model');
    }


    // #[Override()]
    public function update(array $attributes = [], array $options = [])
    {
        throw new Exception('Cannot update read-only model');
    }


    // #[Override()]
    public function delete()
    {
        throw new Exception('Cannot delete read-only model');
    }


    protected function value(): Attribute
    {
        return Attribute::make(
            set: fn () => throw new Exception('Model is readonly'),
        );
    }
}
