<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

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
}
