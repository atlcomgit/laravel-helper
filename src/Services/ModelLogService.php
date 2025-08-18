<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogDriverEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Repositories\ModelLogRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @internal
 * Сервис логирования моделей
 */
class ModelLogService extends DefaultService
{
    public const HIDDEN_VALUE = '••••••';


    /**
     * Сохраняет лог модели при создании записи
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function created(Model $model, ?array $attributes = null): void
    {
        $type = ModelLogTypeEnum::Create;
        $primaryKey = $model->getKeyName();

        $dto = ModelLogDto::create([
            'modelType' => $model::class,
            'modelId' => $model->{$primaryKey}
                ?? (
                    (is_null($attributes) || !$primaryKey)
                    ? null
                    : ($model->{$primaryKey} = $model::query()
                        ->when($attributes, static function ($q) use (&$attributes, &$primaryKey) {
                            if ($primaryKey && array_key_exists($primaryKey, $attributes)) {
                                $q->where($primaryKey, $attributes[$primaryKey] ?? null);

                            } else {
                                foreach ($attributes as $column => $value) {
                                    match (true) {
                                        is_null($value) => $q->whereNull($column),
                                        is_array($value) => $q->whereIn($column, $value),
                                        is_object($value) => $q->whereIn($column, Hlp::castToArray($value)),
                                        is_scalar($value) => $q->where($column, $value),

                                        default => null,
                                    };
                                }
                            }

                            return $q;
                        })
                        // ->when(!$attributes, static fn ($q) => $q->orderByDesc($primaryKey))
                        ->first()
                        ?->{$primaryKey}
                    )
                )
                ?? null,
            'type' => $type,
            'attributes' => $attributes ?? $this->getAttributes($model),
            'changes' => null,
        ]);

        $dto->dispatch();
    }


    /**
     * Сохраняет лог модели при обновлении записи
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function updated(Model $model, ?array $attributes = null): void
    {
        $type = method_exists($model, 'softDeleted')
            ? match (true) {
                !is_null($attributes) && array_key_exists('deleted_at', $attributes) => match (true) {
                        is_null($attributes['deleted_at']) => ModelLogTypeEnum::Restore,

                        default => ModelLogTypeEnum::SoftDelete,
                    },
                $model?->deleted_at !== $model->getOriginal('deleted_at') => ModelLogTypeEnum::SoftDelete,

                default => ModelLogTypeEnum::Update,
            }
            : ModelLogTypeEnum::Update;
        $isSoftDelete = $type == ModelLogTypeEnum::SoftDelete;
        $isRestore = $type == ModelLogTypeEnum::Restore;

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => $this->getChanges($model, $attributes),
        ]);

        !($dto->changes || $isSoftDelete || $isRestore) ?: $dto->dispatch();
    }


    /**
     * Сохраняет лог модели при удалении записи
     *
     * @param Model $model
     * @param array|null $attributes
     * @return void
     */
    public function deleted(Model $model, ?array $attributes = null): void
    {
        $type = match (true) {
            (method_exists($model, 'isForceDeleting') && $model->isForceDeleting())
            => ModelLogTypeEnum::ForceDelete,

            method_exists($model, 'softDeleted')
            && $model->deleted_at
            && $model->deleted_at !== $model->getOriginal('deleted_at')
            => ModelLogTypeEnum::SoftDelete,

            default => ModelLogTypeEnum::Delete,
        };

        if ($type === ModelLogTypeEnum::SoftDelete && !$attributes) {
            return;
        }

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => $type === ModelLogTypeEnum::SoftDelete ? $this->getChanges($model) : null,
        ]);

        $dto->dispatch();
    }


    /**
     * Сохраняет лог модели при восстановлении записи
     *
     * @param Model $model
     * @return void
     */
    public function restored(Model $model): void
    {
        $type = ModelLogTypeEnum::Restore;

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => null,
        ]);

        $dto->dispatch();
    }


    /**
     * Сохраняет лог модели при очистке таблицы
     *
     * @param Model $model
     * @return void
     */
    public function truncated(Model $model): void
    {
        $type = ModelLogTypeEnum::Truncate;

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => null,
        ]);

        $dto->dispatch();
    }


    /**
     * Возвращает изменённые аттрибуты в модели
     *
     * @param Model $model
     * @return array|null
     */
    protected function getAttributes(Model $model): ?array
    {
        $result = null;
        $modelLogExcludeAttributes = property_exists($model, 'modelLogExcludeAttributes')
            ? ($model->modelLogExcludeAttributes ?: [])
            : [];
        $modelLogHiddenAttributes = property_exists($model, 'modelLogHiddenAttributes')
            ? ($model->modelLogHiddenAttributes ?: [])
            : [];

        foreach ($model->getAttributes() as $attribute => $newValue) {
            if (!in_array($attribute, $modelLogExcludeAttributes)) {
                if (in_array($attribute, $modelLogHiddenAttributes)) {
                    $newValue = static::HIDDEN_VALUE;
                }

                $result[$attribute] = $newValue;
            }
        }

        return $result;
    }


    /**
     * Возвращает изменённые аттрибуты в модели
     *
     * @param Model $model
     * @param array|null $attributes
     * @return array|null
     */
    protected function getChanges(Model $model, ?array $attributes = null): ?array
    {
        $result = null;
        $modelLogExcludeAttributes = property_exists($model, 'modelLogExcludeAttributes')
            ? ($model->modelLogExcludeAttributes ?: [])
            : [];
        $modelLogHiddenAttributes = property_exists($model, 'modelLogHiddenAttributes')
            ? ($model->modelLogHiddenAttributes ?: [])
            : [];

        foreach (($model->getAttributes() ?: $attributes) ?? [] as $attribute => $newValue) {
            $oldValue = $model->getOriginal($attribute);
            $newValue = (!is_null($attributes) && array_key_exists($attribute, $attributes))
                ? (
                    method_exists($model, 'getCastedAttribute')
                    ? $model->getCastedAttribute($attribute, $attributes[$attribute])
                    : $attributes[$attribute]
                )
                : $model->$attribute;

            if (!in_array($attribute, $modelLogExcludeAttributes) && $this->hasDifference($oldValue, $newValue)) {
                if (in_array($attribute, $modelLogHiddenAttributes)) {
                    $newValue = $oldValue = static::HIDDEN_VALUE;
                }

                $result[$attribute] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $result;
    }


    /**
     * Проверяет два значения на разницу
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    protected function hasDifference(mixed $oldValue, mixed $newValue): bool
    {
        return !match (true) {
            is_numeric($oldValue) && is_numeric($newValue) => (string)(float)$oldValue === (string)(float)$newValue,

            is_float($oldValue) && is_float($newValue) => (string)$oldValue === (string)$newValue,

            is_scalar($oldValue) && is_scalar($newValue) => $oldValue === $newValue,

            is_array($oldValue) && is_array($newValue) => (bool)(
                array_udiff_uassoc(
                    $oldValue,
                    $newValue,
                    fn ($a, $b) => $this->hasDifference($a, $b),
                    fn ($a, $b) => $this->hasDifference($a, $b),
                )
            ),

            is_object($oldValue) && is_object($newValue) => (bool)(
                method_exists($oldValue, 'toArray') && method_exists($newValue, 'toArray')
                ? array_udiff_uassoc(
                    $oldValue->toArray(),
                    $newValue->toArray(),
                    fn ($a, $b) => (int)$this->hasDifference($a, $b),
                    fn ($a, $b) => (int)$this->hasDifference($a, $b),
                )
                : array_udiff_uassoc(
                    (array)$oldValue,
                    (array)$newValue,
                    fn ($a, $b) => (int)$this->hasDifference($a, $b),
                    fn ($a, $b) => (int)$this->hasDifference($a, $b),
                )
            ),

            default => json_encode($oldValue) === json_encode($newValue),
        };
    }


    /**
     * Логирование модели
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function log(ModelLogDto $dto): void
    {
        $drivers = Lh::config(ConfigEnum::ModelLog, 'drivers', []);

        foreach ($drivers as $driver) {
            try {
                !is_string($driver) ?: $driver = trim($driver);

                switch (ModelLogDriverEnum::enumFrom($driver)) {
                    case ModelLogDriverEnum::File:
                        if ($file = Lh::config(ConfigEnum::ModelLog, 'file')) {
                            file_put_contents(
                                $file,
                                now()->format('d-m-Y H:i:s') . ' '
                                . json_encode($dto, Hlp::jsonFlags())
                                . PHP_EOL,
                                FILE_APPEND,
                            );
                        }
                        break;

                    case ModelLogDriverEnum::Database:
                        app(ModelLogRepository::class)->create($dto);
                        break;

                    case ModelLogDriverEnum::Telegram:
                        throw new Exception('Драйвер не реализован');

                    default:
                        !$driver ?: throw new Exception('Драйвер лога не найден');
                }

            } catch (Throwable $exception) {
                $this->telegram($dto, $exception);
            }
        }
    }


    /**
     * Очищает логи моделей
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!Lh::config(ConfigEnum::ModelLog, 'enabled')) {
            return 0;
        }

        return app(ModelLogRepository::class)->cleanup($days);
    }


    /**
     * Отправка сообщения в телеграм
     *
     * @param ModelLogDto $dto
     * @param Throwable $exception
     * @return void
     */
    public function telegram(ModelLogDto $dto, Throwable $exception): void
    {
        telegram($exception);
    }
}
