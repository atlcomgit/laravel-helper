<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ResourceEnum;
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
     * Формирует массив ресурса; ответам форм добавляет описания полей модели в ключ descriptions.
     * Явно заданный ресурсом ключ descriptions имеет приоритет над описаниями модели.
     * @see \Atlcom\LaravelHelper\Tests\Unit\ModelDescriptionsTest
     *
     * @param Request|null $request
     * @return array
     */
    public function resolve($request = null)
    {
        $data = parent::resolve($request);

        if (
            !is_array($data)
            || array_key_exists('descriptions', $data)
            || !in_array(
                $this->structure,
                [ResourceEnum::Form, ResourceEnum::FormCreate, ResourceEnum::FormUpdate],
                true,
            )
            || !is_object($this->resource)
            || !method_exists($this->resource, 'getDescriptions')
        ) {
            return $data;
        }

        $descriptions = $this->resource->getDescriptions();

        return $descriptions ? [...$data, 'descriptions' => $descriptions] : $data;
    }


    /**
     * Возвращает значение если заполнено
     *
     * @param mixed $value
     * @return mixed
     */
    public function whenFilled(mixed $value): mixed
    {
        return $this->when(
            Hlp::castToBool($value),
            static fn () => is_object($value) && is_callable($value) ? $value() : $value,
        );
    }
}
