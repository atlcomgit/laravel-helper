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
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use UnitEnum;
use BackedEnum;
use DateTimeInterface;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Stringable;
use Traversable;
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
            'modelId' => (string)(
                $model->{$primaryKey}
                ?? (
                    (\is_null($attributes) || !$primaryKey)
                    ? null
                    : ($model->{$primaryKey} = $model::query()
                        ->when($attributes, static function ($q) use (&$attributes, &$primaryKey) {
                            if ($primaryKey && \array_key_exists($primaryKey, $attributes)) {
                                $q->where($primaryKey, $attributes[$primaryKey] ?? null);

                            } else {
                                foreach ($attributes as $column => $value) {
                                    match (true) {
                                        \is_null($value) => $q->whereNull($column),
                                        // is_array($value) => $q->whereRaw("{$column}::text = ?", [Hlp::castToJson($value)]),
                                        // is_object($value) => $q->where("{$column}::text = ?", [Hlp::castToJson($value)]),
                                        \is_scalar($value) => $q->where($column, $value),

                                        default => null,
                                    };
                                }
                            }

                            return $q;
                        })
                        // ->when(!$attributes, static fn ($q) => $q->orderByDesc($primaryKey))
                        ->when($primaryKey, static fn ($q) => $q->orderByDesc($primaryKey))
                        ->first()
                        ?->{$primaryKey}
                    )
                )
                ?? null
            ),
            'type' => $type,
            'attributes' => $attributes ?? $this->getAttributes($model),
            'changes' => null,
        ]);

        !$dto->modelId ?: Hlp::cacheRuntime($dto->getHash(), static fn () => $dto->dispatch());
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
                !\is_null($attributes) && \array_key_exists('deleted_at', $attributes) => match (true) {
                        \is_null($attributes['deleted_at']) => ModelLogTypeEnum::Restore,

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
            'modelId' => $model->{$model->getKeyName()} ?? null,
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
            'modelId' => $model->{$model->getKeyName()} ?? null,
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
            'modelId' => $model->{$model->getKeyName()} ?? null,
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
            'modelId' => $model->{$model->getKeyName()} ?? null,
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
            if (!\in_array($attribute, $modelLogExcludeAttributes)) {
                if (\in_array($attribute, $modelLogHiddenAttributes)) {
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

        foreach (($attributes ?: $model->getAttributes()) ?? [] as $attribute => $newValue) {
            $oldValue = $model->getOriginal($attribute) ?? $model->getAttribute($attribute);
            $newValue = (!\is_null($attributes) && \array_key_exists($attribute, $attributes))
                ? (
                    method_exists($model, 'getCastedAttribute')
                    ? $model->getCastedAttribute($attribute, $attributes[$attribute])
                    : $attributes[$attribute]
                )
                : $model->$attribute;

            if (!\in_array($attribute, $modelLogExcludeAttributes) && $this->hasDifference($oldValue, $newValue)) {
                $oldFormattedValue = $this->normalizeDifferenceValue($oldValue);
                $newFormattedValue = $this->normalizeDifferenceValue($newValue);

                if (\in_array($attribute, $modelLogHiddenAttributes)) {
                    $newFormattedValue = $oldFormattedValue = static::HIDDEN_VALUE;
                }

                $result[$attribute] = [
                    'old' => $oldFormattedValue,
                    'new' => $newFormattedValue,
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
        $oldValue = $this->normalizeDifferenceValue($oldValue);
        $newValue = $this->normalizeDifferenceValue($newValue);

        return !match (true) {
            is_numeric($oldValue) && is_numeric($newValue) => (string)(float)$oldValue === (string)(float)$newValue,

            \is_scalar($oldValue) && \is_scalar($newValue) => $oldValue === $newValue,

            \is_array($oldValue) && \is_array($newValue) => json_encode($oldValue, Hlp::jsonFlags())
            === json_encode($newValue, Hlp::jsonFlags()),

            \is_object($oldValue) && \is_object($newValue) => json_encode((array)$oldValue, Hlp::jsonFlags())
            === json_encode((array)$newValue, Hlp::jsonFlags()),

            \is_resource($oldValue) && \is_resource($newValue) => get_resource_id($oldValue) === get_resource_id($newValue),

            default => json_encode($oldValue, Hlp::jsonFlags()) === json_encode($newValue, Hlp::jsonFlags()),
        };
    }


    /**
     * Нормализует значение для корректного сравнения
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeDifferenceValue(mixed $value): mixed
    {
        return match (true) {
            \is_array($value) => $this->normalizeDifferenceArray($value),

            $value instanceof BackedEnum => $value->value,

            $value instanceof UnitEnum => $value->name,

            $value instanceof DateTimeInterface => DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new DateTimeZone(config('app.timezone') ?? 'UTC'))
                ->format('Y-m-d H:i:s'),

            $value instanceof Stringable => (string)$value,

            $value instanceof Model => $this->normalizeDifferenceArray($value->attributesToArray()),

            $value instanceof Collection => $this->normalizeDifferenceArray($value->toArray()),

            $value instanceof Arrayable => $this->normalizeDifferenceArray($value->toArray()),

            $value instanceof JsonSerializable => $this->normalizeDifferenceValue($value->jsonSerialize()),

            $value instanceof Traversable => $this->normalizeDifferenceArray(iterator_to_array($value)),

            default => $value,
        };
    }


    /**
     * Нормализует массив с учётом порядка ключей
     *
     * @param array $value
     * @return array
     */
    protected function normalizeDifferenceArray(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeDifferenceValue($item), $value);
        }

        $result = [];

        foreach ($value as $key => $item) {
            $result[$key] = $this->normalizeDifferenceValue($item);
        }

        ksort($result);

        return $result;
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
                !\is_string($driver) ?: $driver = trim($driver);

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
