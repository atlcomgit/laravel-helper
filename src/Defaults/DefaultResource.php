<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Абстрактный класс для ресурсов
 */
abstract class DefaultResource extends JsonResource
{
    public $with = [
        // 'status' => true,
    ];


    public function with(Request $request)
    {
        return [
            ...((isLocal() || isDebug())
                ? ['Resource' => class_basename($this)]
                : []
            ),
        ];
    }
}
