<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Dto;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * Абстрактный класс для Dto
 * @abstract
 * 
 * @override @see self::rules()
 * @override @see self::mappings()
 * @override @see self::defaults()
 * @override @see self::casts()
 * @override @see self::exceptions()
 * 
 * @override @see self::onCreating()
 * @override @see self::onCreated()
 * @override @see self::onFilling()
 * @override @see self::onFilled()
 * @override @see self::onMerging()
 * @override @see self::onMerged()
 * @override @see self::onSerializing()
 * @override @see self::onSerialized()
 * @override @see self::onAssigning()
 * @override @see self::onAssigned()
 * @override @see self::onException()
 */
abstract class DefaultDto extends Dto implements Arrayable
{
    /**
     * @inheritDoc
     * Включает опцию авто приведения объектов при заполнении dto
     */
    const AUTO_CASTS_OBJECTS_ENABLED = true;

    /**
     * @inheritDoc
     * Включает реализацию интерфейса ArrayAccess для работы с dto как с массивом
     */
    const INTERFACE_ARRAY_ACCESS_ENABLED = true;


    /**
     * @inheritDoc
     * @see parent::__construct()
     *
     * @param array|object|string|null $data
     */
    public function __construct(array|object|string|null $constructData = null)
    {
        parent::__construct($constructData);
    }


    /**
     * @inheritDoc
     * @see parent::rules()
     *
     * @return array
     */
    // #[Override()]
    public function rules(): array
    {
        return [];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     */
    // #[Override()]
    protected function casts(): array
    {
        return [...parent::getCasts(), ...parent::castDefault()];
    }


    /**
     * Заполнение dto из модели
     *
     * @param Model $model
     * @return static
     */
    public function fillFromModel(Model $model): static
    {
        return $this->fillFromData($model);
    }
}
