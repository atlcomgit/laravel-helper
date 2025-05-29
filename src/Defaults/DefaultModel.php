<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Enums\ModelConfigFilesEnum;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Exception;

/**
 * Абстрактный класс модели по умолчанию
 *
 * @property int $id
 * @property array $config
 * @property string $morph_name
 */
abstract class DefaultModel extends Model
{
    use ModelLogTrait;
    // use ModelHasFilesTrait;


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
     * [Description for toResource]
     *
     * @param string|BackedEnum|null $resourceClass
     * @return JsonResource
     */
    // #[Override()]
    public function toResource(string|BackedEnum|null $resourceClass = null): JsonResource
    {
        static::$resource = $resourceClass;

        return parent::toResource();
    }


    // #[Override()]
    public static function guessResourceName(): array
    {
        $configResources = static::getModelConfig(static::class)[ModelConfigFilesEnum::ModelConfigResources->value];

        return match (true) {
            is_string(static::$resource) => $configResources[static::$resource] ?? [],
            static::$resource instanceof BackedEnum => $configResources[static::$resource->value] ?? [],

            default => [],
        };
    }
}
