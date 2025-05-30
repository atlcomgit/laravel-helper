<?php

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ModelLogDriverEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Jobs\ModelLogJob;
use Atlcom\LaravelHelper\Repositories\ModelLogRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Сервис логирования моделей
 */
class ModelLogService
{
    public const HIDDEN_VALUE = '••••••';


    /**
     * Сохраняет лог модели при создании записи
     *
     * @param Model $model
     * @return void
     */
    public function created(Model $model): void
    {
        $type = ModelLogTypeEnum::Create;

        $dto = ModelLogDto::create([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => null,
        ]);

        $this->dispatch($dto);
    }


    /**
     * Сохраняет лог модели при обновлении записи
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        $type = (
            method_exists($model, 'softDeleted')
            && $model->deleted_at
            && $model->deleted_at !== $model->getOriginal('deleted_at')
        )
            ? ModelLogTypeEnum::SoftDelete
            : ModelLogTypeEnum::Update;
        $isSoftDelete = $type == ModelLogTypeEnum::SoftDelete;

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => $this->getChanges($model),
        ]);

        !($dto->changes || $isSoftDelete) ?: $this->dispatch($dto);
    }


    /**
     * Сохраняет лог модели при удалении записи
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
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

        $dto = ModelLogDto::fill([
            'modelType' => $model::class,
            'modelId' => $model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes($model),
            'changes' => $type === ModelLogTypeEnum::SoftDelete ? $this->getChanges($model) : null,
        ]);

        $this->dispatch($dto);
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

        $this->dispatch($dto);
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
        $excludeAttributes = property_exists($model, 'logExcludeAttributes')
            ? ($model->logExcludeAttributes ?: [])
            : [];
        $hideAttributes = property_exists($model, 'logHideAttributes')
            ? ($model->logHideAttributes ?: [])
            : [];

        foreach ($model->getAttributes() as $attribute => $newValue) {
            if (!in_array($attribute, $excludeAttributes)) {
                if (in_array($attribute, $hideAttributes)) {
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
     * @return array|null
     */
    protected function getChanges(Model $model): ?array
    {
        $result = null;
        $excludeAttributes = property_exists($model, 'logExcludeAttributes')
            ? ($model->logExcludeAttributes ?: [])
            : [];
        $hideAttributes = property_exists($model, 'logHideAttributes')
            ? ($model->logHideAttributes ?: [])
            : [];

        foreach ($model->getAttributes() as $attribute => $newValue) {
            $oldValue = $model->getOriginal($attribute);
            $newValue = $model->$attribute;

            if (!in_array($attribute, $excludeAttributes) && $this->hasDifference($oldValue, $newValue)) {
                if (in_array($attribute, $hideAttributes)) {
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
            is_float($oldValue) && is_float($newValue) => (string)$oldValue == (string)$newValue,

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
     * Отправляет данные в очередь
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function dispatch(ModelLogDto $dto): void
    {
        isTesting()
            ? ModelLogJob::dispatchSync($dto)
            : ModelLogJob::dispatch($dto);
    }


    /**
     * Логирование модели
     *
     * @param ModelLogDto $dto
     * @return void
     */
    public function log(ModelLogDto $dto): void
    {
        $drivers = config('laravel-helper.model_log.drivers', []);

        foreach ($drivers as $driver) {
            try {
                !is_string($driver) ?: $driver = trim($driver);

                switch (ModelLogDriverEnum::enumFrom($driver)) {
                    case ModelLogDriverEnum::File:
                        if ($file = config('laravel-helper.model_log.file')) {
                            file_put_contents(
                                $file,
                                now()->format('d-m-Y H:i:s') . ' '
                                . json_encode($dto, Helper::jsonFlags())
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

            } catch (Throwable $e) {
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
        if (!config('laravel-helper.model_log.enabled')) {
            return 0;
        }

        return app(ModelLogRepository::class)->cleanup($days);
    }
}
