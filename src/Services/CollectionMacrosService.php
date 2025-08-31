<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Сервис регистрации Collection макросов
 */
class CollectionMacrosService extends DefaultService
{
    /**
     * Добавляет макросы в коллекции
     *
     * @return void
     */
    public static function setMacros(): void
    {
        !method_exists(Hlp::class, 'objectToArrayRecursive')
            ?: Collection::macro(
                'toArrayRecursive',
                fn () => /** @var Collection $this */ Hlp::objectToArrayRecursive($this)
            );

        Collection::macro(
            'toResource',
            fn (UnitEnum|string|null $structure)
            => JsonResource::collection(
                /** @var Collection $this */
                $this->map(static fn (mixed $item) => match (true) {
                    $item instanceof DefaultModel => $item->toResource($structure),
                    $item instanceof Model => $item->toArray(),

                    default => $item,
                }),
            )
                ->additional([
                    'count' => $this->count(),
                    'stamp' => now(),
                ])
        );
    }
}
