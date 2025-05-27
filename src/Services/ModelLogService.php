<?php

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Jobs\ModelLogJob;
use Illuminate\Database\Eloquent\Model;

class ModelLogService
{
    public const HIDDEN_VALUE = '••••••';


    public function __construct(protected Model $model) {}


    /**
     * Сохраняет лог модели при создании записи
     *
     * @return void
     */
    public function created(): void
    {
        $type = ModelLogTypeEnum::Create;

        $dto = ModelLogDto::create([
            'modelType' => $this->model::class,
            'modelId' => $this->model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes(),
            'changes' => null,
        ]);

        $this->dispatch($dto);
    }


    /**
     * Сохраняет лог модели при обновлении записи
     *
     * @return void
     */
    public function updated(): void
    {
        $type = (
            method_exists($this->model, 'softDeleted')
            && $this->model->deleted_at
            && $this->model->deleted_at !== $this->model->getOriginal('deleted_at')
        )
            ? ModelLogTypeEnum::SoftDelete
            : ModelLogTypeEnum::Update;
        $isSoftDelete = $type == ModelLogTypeEnum::SoftDelete;

        $dto = ModelLogDto::fill([
            'modelType' => $this->model::class,
            'modelId' => $this->model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes(),
            'changes' => $this->getChanges(),
        ]);

        !($dto->changes || $isSoftDelete) ?: $this->dispatch($dto);
    }


    /**
     * Сохраняет лог модели при удалении записи
     *
     * @return void
     */
    public function deleted(): void
    {
        $type = match (true) {
            (method_exists($this->model, 'isForceDeleting') && $this->model->isForceDeleting())
            => ModelLogTypeEnum::ForceDelete,

            method_exists($this->model, 'softDeleted')
            && $this->model->deleted_at
            && $this->model->deleted_at !== $this->model->getOriginal('deleted_at')
            => ModelLogTypeEnum::SoftDelete,

            default => ModelLogTypeEnum::Delete,
        };

        $dto = ModelLogDto::fill([
            'modelType' => $this->model::class,
            'modelId' => $this->model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes(),
            'changes' => $type === ModelLogTypeEnum::SoftDelete ? $this->getChanges() : null,
        ]);

        $this->dispatch($dto);
    }


    /**
     * Сохраняет лог модели при восстановлении записи
     *
     * @return void
     */
    public function restored(): void
    {
        $type = ModelLogTypeEnum::Restore;

        $dto = ModelLogDto::fill([
            'modelType' => $this->model::class,
            'modelId' => $this->model->id ?? null,
            'type' => $type,
            'attributes' => $this->getAttributes(),
            'changes' => null,
        ]);

        $this->dispatch($dto);
    }


    /**
     * Возвращает изменённые аттрибуты в модели
     *
     * @return array|null
     */
    protected function getAttributes(): ?array
    {
        $result = null;
        $excludeAttributes = property_exists($this->model, 'logExcludeAttributes')
            ? ($this->model->logExcludeAttributes ?: [])
            : [];
        $hideAttributes = property_exists($this->model, 'logHideAttributes')
            ? ($this->model->logHideAttributes ?: [])
            : [];

        foreach ($this->model->getAttributes() as $attribute => $newValue) {
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
     * @return array|null
     */
    protected function getChanges(): ?array
    {
        $result = null;
        $excludeAttributes = property_exists($this->model, 'logExcludeAttributes')
            ? ($this->model->logExcludeAttributes ?: [])
            : [];
        $hideAttributes = property_exists($this->model, 'logHideAttributes')
            ? ($this->model->logHideAttributes ?: [])
            : [];

        foreach ($this->model->getAttributes() as $attribute => $newValue) {
            $oldValue = $this->model->getOriginal($attribute);
            $newValue = $this->model->$attribute;

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
}
