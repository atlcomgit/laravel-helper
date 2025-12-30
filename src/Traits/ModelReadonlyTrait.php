<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Exceptions\LaravelHelperException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Override;

/**
 * Трейт для read-only моделей
 * 
 * @mixin DefaultModel
 */
trait ModelReadonlyTrait
{
    /**
     * Выбрасывает исключение о том, что модель доступна только для чтения
     *
     * @return never
     */
    public static function throw(): never
    {
        throw new LaravelHelperException('Модель ' . static::class . ' доступна только для чтения', 500);
    }


    /**
     * Запрещаем запись модели
     *
     * @param array $options
     * @return bool
     */
    #[Override()]
    public function save(array $options = []): bool
    {
        static::throw();
    }


    /**
     * Запрещаем удаление модели
     *
     * @return bool
     */
    #[Override()]
    public function delete(): bool
    {
        static::throw();
    }


    /**
     * Запрещаем обновление модели
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    #[Override()]
    public function update(array $attributes = [], array $options = []): bool
    {
        static::throw();
    }


    /**
     * Запрещаем массовое обновление модели
     *
     * @return Attribute
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            set: fn () => static::throw(),
        );
    }
}
