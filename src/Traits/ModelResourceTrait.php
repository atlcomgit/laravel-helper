<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use BackedEnum;
use Carbon\Carbon;
use DateTime;
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
            Hlp::arrayDeleteKeys($model::getModelCasts(), ['deleted_at'])
        );
    }
}
