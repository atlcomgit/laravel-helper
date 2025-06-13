<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use BackedEnum;

/**
 * Трейт преобразования объекта в массив
 */
trait ArrayableTrait
{
    /**
     * Преобразует объект в массив рекурсивно
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->toArrayRecursive(get_object_vars($this));
    }


    /**
     * Рекурсивно преобразует массив
     *
     * @param array $array
     * @return array
     */
    private function toArrayRecursive(array $array): mixed
    {
        return array_map(fn ($item) => match (true) {
            $item instanceof BackedEnum => $item->value,
            is_string($item) && Hlp::regexpValidateJson($item) => $this->toArrayRecursive(json_decode($item, true)),
            is_object($item) && method_exists($item, 'toArray') => $this->toArrayRecursive($item->toArray()),
            is_object($item) && method_exists($item, 'all') => $this->toArrayRecursive($item->all()),
            is_object($item) => (array)$item,
            is_array($item) => $this->toArrayRecursive($item),
            is_object($item) && is_callable($item) => (($item = $item()) && (is_object($item) || is_array($item))
                ? $this->toArrayRecursive($item)
                : $item
            ),

            default => $item,
        }, $array);
    }
}
