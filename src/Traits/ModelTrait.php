<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Enums\ModelConfigFilesEnum;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Exception;

/**
 * Трейт для расширения модели
 * 
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait ModelTrait
{
    // public const CONFIG = DefaultModelConfig::class;

    public static BackedEnum|string|null $resource = null;


    /**
     * Возвращает название таблицы модели
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return with(new static)->getTable();
    }


    /**
     * Возвращает описание таблицы
     *
     * @return string
     */
    public static function getTableComment(): string
    {
        return match (true) {
            property_exists(static::class, 'comment') => (new static)->comment,
            defined(static::class . '::COMMENT') => static::COMMENT,

            default => '',
        };
    }


    /**
     * Возвращает список полей модели
     *
     * @return array
     */
    public static function getTableFields(): array
    {
        return [
            ...[static::getPrimaryKeyName() => 'ID'],
            ...array_fill_keys(array_keys(static::getModelCasts()), null),
            ...with(new static)->getFields(),
        ];
    }


    /**
     * Возвращает имя первичного ключа
     *
     * @return string
     */
    public static function getPrimaryKeyName(): string
    {
        return with(new static)->getKeyName();
    }


    /**
     * Возвращает тип первичного ключа
     *
     * @return string
     */
    public static function getPrimaryKeyType(): string
    {
        return with(new static)->getKeyType();
    }


    /**
     * возвращает массив с константами настроек у модели
     *
     * @return array
     */
    public function getConfigAttribute(): array
    {
        $modelClass = get_class($this);
        $constantConfig = "{$modelClass}::CONFIG";
        $configClass = defined($constantConfig) ? constant($constantConfig) : null;
        $config = ($configClass && method_exists($configClass, 'data')) ? $configClass::data() : [];

        return [
            ...(array)$config,
            'model_class' => get_class($this),
        ];
    }


    /**
     * аттрибут: возвращает MORPH_NAME
     *
     * @return string|null
     */
    public function getMorphNameAttribute(): ?string
    {
        return self::getModelConfigMorphName($this);
    }


    public static function getMorphName(): ?string
    {
        return self::getModelConfigMorphName(new static());
    }


    /**
     * возвращает тип модели
     *
     * @return string
     */
    public static function getModelConfig(string $modelClass): array
    {
        $constantConfig = "{$modelClass}::CONFIG";
        $configClass = defined($constantConfig) ? constant($constantConfig) : null;

        if (!class_exists($configClass)) {
            throw new Exception(
                class_basename($configClass)
                . ': Класс настроек модели не найден',
                400,
            );
        }

        if (!method_exists($configClass, 'data')) {
            throw new Exception(
                class_basename($configClass)
                . '::data()'
                . ': Статический метод настроек не найден',
                400,
            );
        }

        return (array)$configClass::data();
    }


    /**
     * возвращает тип модели
     *
     * @return string
     */
    public static function getModelConfigMorphName(?Model $model = null): string
    {
        $model ??= new static();

        if (defined($model::class . '::MORPH_NAME')) {

            return $model::MORPH_NAME;
        }

        $config = static::getModelConfig(get_class($model));

        $morphName = ($model->morph_name ?? null)
            ?: ($config[ModelConfigFilesEnum::ModelConfigMorphName->value] ?? null)
            ?: 'default';

        $morphName = match (mb_strtolower($morphName)) {
            'default' => (static function () use ($model) {
                    $modelClass = Str::upper(Str::snake(class_basename($model::class)));
                    if (substr($modelClass, -1) !== 'S') {
                        $modelClass .= 'S';
                    }
                    return $modelClass;
                })(),
            default => $morphName,
        };

        if (!$morphName) {
            $modelClass = $model->config[ModelConfigFilesEnum::ModelConfigClass->value] ?? '';
            $constantConfig = "{$modelClass}::CONFIG";
            $configClass = defined($constantConfig) ? constant($constantConfig) : null;

            throw new Exception(
                class_basename($configClass)
                . '->' . ModelConfigFilesEnum::ModelConfigMorphName->value
                . ': Не указан тип модели',
                400,
            );
        }

        return $morphName;
    }


    /**
     * Возвращает массив приведений типов свойств модели
     *
     * @return array
     */
    public static function getModelCasts(): array
    {
        return (new static())->getCasts();
    }


    /**
     * Возвращает массив дополнительных свойств модели
     *
     * @return array
     */
    public function getModelAppends(): array
    {
        return $this->getArrayableAppends();
    }


    /**
     * Возвращает массив свойств модели
     *
     * @return array
     */
    public static function getModelKeys(): array
    {
        $model = new static();

        return array_merge(
            array_keys($model->getCasts()),
            array_keys($model->getModelCasts()),
        );
    }


    /**
     * Возвращает приведение к типу свойства модели
     *
     * @param string $attribute
     * @param mixed|null $value
     * @return mixed
     */
    public function getCastedAttribute(string $attribute, mixed $value = null): mixed
    {
        return (array_key_exists($attribute, $this->getCasts())
            ? $this->castAttribute($attribute, $value ?? $this->getAttributes()[$attribute] ?? null)
            : $value
        ) ?? $this->$attribute ?? null;
    }


    /**
     * Возвращает названия полей модели
     *
     * @return array
     */
    public function getFields(): array
    {
        return property_exists($this, 'fields') ? $this->fields : [];
    }
}
