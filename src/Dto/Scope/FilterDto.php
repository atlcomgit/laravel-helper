<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Scope;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\FilterComponentEnum;
use Atlcom\LaravelHelper\Enums\FilterOperatorEnum;
use Closure;
use Illuminate\Support\Collection;
use Override;

/**
 * Класс dto для фильтров
 */
class FilterDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public FilterComponentEnum|string|null $component;
    public ?FilterOperatorEnum $operator;
    public ?string $label;
    public ?string $column;
    public ?array $items;
    public ?Closure $closure;


    /**
     * Возвращает фильтр: Текстовое поле
     *
     * @param string $label
     * @param string $column
     * @param FilterOperatorEnum $operator
     * @return array
     */
    public static function input(string $label, string $column, FilterOperatorEnum $operator): array
    {
        return static::create(
            component: FilterComponentEnum::Input,
            label: $label,
            column: $column,
            operator: $operator,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор одного значения из списка
     *
     * @param string $label
     * @param string $column
     * @param array|Collection $items
     * @return array
     */
    public static function select(string $label, string $column, array|Collection $items): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxRadio,
            label: $label,
            column: $column,
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::Equal,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор нескольких значений из списка
     *
     * @param string $label
     * @param string $column
     * @param array|Collection $items
     * @return array
     */
    public static function multiple(string $label, string $column, array|Collection $items): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxCheck,
            label: $label,
            column: $column,
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::In,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор нескольких значений из списка с замыканием
     *
     * @param string $label
     * @param array|Collection $items
     * @param Closure $closure
     * @return array
     */
    public static function query(string $label, array|Collection $items, Closure $closure): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxCheck,
            label: $label,
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::Closure,
            closure: $closure,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор интервала дат
     *
     * @param string $label
     * @param string $column
     * @return array
     */
    public static function dates(string $label, string $column): array
    {
        return static::create(
            component: FilterComponentEnum::DateInterval,
            label: $label,
            column: $column,
            operator: FilterOperatorEnum::Between,
        )->toArray();
    }


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull();
    }
}
