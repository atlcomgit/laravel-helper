<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use BackedEnum;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use UnitEnum;

/**
 * Трейт для подключения ресурса к модели
 * 
 * @mixin \Atlcom\LaravelHelper\Defaults\DefaultModel
 */
trait ModelResourceTrait
{
    /**
     * Общий ресурс модели
     *
     * @param UnitEnum|string|null $structure
     * @return JsonResource
     */
    public function toResource(UnitEnum|string|null $structure = null): JsonResource
    {
        return JsonResource::make($this);
    }


    /**
     * Подготавливает значения атрибутов модели к использованию в HTML-формах с учётом casts.
     * Преобразует даты, булевы значения, коллекции, JSON и т.п. к форматам, удобным для отображения и редактирования.
     *
     * @param self $model
     * @return array
     */
    public static function getModelCastsToForm(self $model): array
    {
        return array_map(
            static fn ($item) => match (true) {
                is_string($item) && class_exists($item) && is_subclass_of($item, BackedEnum::class)
                => $item::enumLabels(),
                is_string($item) && $item === Carbon::class => 'datetime',
                is_string($item) && class_exists($item) && is_subclass_of($item, DateTime::class) => 'datetime',

                default => $item,
            },
            Hlp::arrayDeleteKeys($model::getModelCasts(), ['deleted_at']),
        );
    }


    /**
     * Возвращает список записей для заполнения выбираемого поля в форме.
     * Используется для генерации названий в интерфейсе, админ-панели и сообщениях.
     *
     * @param string $columnId
     * @param string $columnName
     * @param string|null $columnComment
     * @param mixed 
     * @return Collection<static>
     */
    public static function getModelItemsForForm(
        ?string $columnId = null,
        string $columnName = 'name',
        ?string $columnComment = null,
        int|bool|null $withCache = null,
    ): Collection {
        $columnId ??= method_exists(static::class, 'getPrimaryKeyName') ? static::getPrimaryKeyName() : 'id';

        return static::getModelLabels($columnId, $columnName, $columnComment, $withCache);
    }


    /**
     * Возвращает метки (человеко-читаемые названия) для модели.
     * Используется для генерации названий в интерфейсе, админ-панели и сообщениях.
     *
     * @param string $columnId
     * @param string $columnName
     * @param string|null $columnComment
     * @return Collection<static>
     */
    public static function getModelLabels(
        string $columnId = 'id',
        string $columnName = 'name',
        ?string $columnComment = null,
        int|bool|null $withCache = null,
    ): Collection {
        return static::query()
            ->select([
                "{$columnName} as label",
                "{$columnId} as value",
                ...($columnComment ? ["{$columnComment} as comment"] : []),
            ])
            ->limit(1000)
            ->withCache($withCache)
            ->get();
    }


    /**
     * Возвращает названия полей модели
     *
     * @return array
     */
    public static function getModelFields(): array
    {
        return [
            static::getPrimaryKeyName(),
            ...array_fill_keys(array_keys(static::getModelCasts()), null),
            ...static::getTableFields(),
        ];
    }
}
