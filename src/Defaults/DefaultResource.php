<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Hlp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use UnitEnum;

/**
 * Абстрактный класс для ресурсов
 */
abstract class DefaultResource extends JsonResource
{
    // public static $wrap = 'data';
    private UnitEnum|string|null $structure = null;


    public $with = [
        // 'status' => true,
    ];


    public function with(Request $request)
    {
        return [
            ...((isLocal() || isDev() || isDebug())
                ? ['Resource' => class_basename($this), 'structure' => $this->structure]
                : []
            ),
        ];
    }


    /**
     * Устанавливает структуру ресурса
     *
     * @param UnitEnum|string|null $structure
     * @return static
     */
    public function setStructure(UnitEnum|string|null $structure): static
    {
        $this->structure = $structure;

        return $this;
    }


    /**
     * Возвращает структуру ресурса
     *
     * @return UnitEnum|string|null
     */
    public function getStructure(): UnitEnum|string|null
    {
        return $this->structure;
    }


    /**
     * Возвращает значение если заполнено
     *
     * @param mixed $value
     * @return mixed
     */
    public function whenFilled(mixed $value): mixed
    {
        return $this->when(Hlp::castToBool($value), static fn () => $value);
    }
}
