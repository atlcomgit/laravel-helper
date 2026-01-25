<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\FilterComponentEnum;
use Atlcom\LaravelHelper\Enums\FilterOperatorEnum;
use Closure;
use Illuminate\Support\Collection;
use Override;

/**
 * Dto настройки фильтров таблицы CRUD модели
 */
class TableFilterDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public FilterComponentEnum|string|null $component;
    public ?FilterOperatorEnum             $operator;
    public ?string                         $label;
    public ?string                         $column;
    public ?array                          $items;
    public ?Closure                        $closure;


    /**
     * Возвращает фильтр: Текстовое поле
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @param FilterOperatorEnum $operator
     * @param Closure|null $closure
     * @return array
     */
    public static function input(
        string $modelClassOrLabel,
        string $column,
        FilterOperatorEnum $operator,
        ?Closure $closure = null,
    ): array {
        return static::create(
            component: FilterComponentEnum::Input,
            label: static::getLabel($modelClassOrLabel, $column),
            column: $column,
            operator: $operator,
            closure: $closure,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Поле между интервалом
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @param Closure|null $closure
     * @return array
     */
    public static function between(string $modelClassOrLabel, string $column, ?Closure $closure = null): array
    {
        return static::create(
            component: FilterComponentEnum::InputBetween,
            label: static::getLabel($modelClassOrLabel, $column),
            column: $column,
            operator: FilterOperatorEnum::Between,
            closure: $closure,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор одного значения из списка
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @param array|Collection $items
     * @return array
     */
    public static function select(string $modelClassOrLabel, string $column, array|Collection $items): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxRadio,
            label: static::getLabel($modelClassOrLabel, $column),
            column: $column,
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::Equal,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор нескольких значений из списка
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @param array|Collection $items
     * @return array
     */
    public static function multiple(string $modelClassOrLabel, string $column, array|Collection $items): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxCheck,
            label: static::getLabel($modelClassOrLabel, $column),
            column: $column,
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::In,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор нескольких значений из списка с замыканием
     *
     * @param string $modelClassOrLabel
     * @param array|Collection $items
     * @param Closure $closure
     * @return array
     */
    public static function query(string $modelClassOrLabel, string $column, array|Collection $items, Closure $closure): array
    {
        return static::create(
            component: FilterComponentEnum::ComboboxCheck,
            label: static::getLabel($modelClassOrLabel, $column),
            items: $items instanceof Collection ? $items->all() : $items,
            operator: FilterOperatorEnum::Closure,
            closure: $closure,
        )->toArray();
    }


    /**
     * Возвращает фильтр: Выбор интервала дат
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @param Closure|null $closure
     * @return array
     */
    public static function dates(string $modelClassOrLabel, string $column, ?Closure $closure = null): array
    {
        return static::create(
            component: FilterComponentEnum::DateInterval,
            label: static::getLabel($modelClassOrLabel, $column),
            column: $column,
            operator: FilterOperatorEnum::Between,
            closure: $closure,
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


    /**
     * Возвращает название поля модели
     *
     * @param string $modelClassOrLabel
     * @param string $column
     * @return string
     */
    public static function getLabel(string $modelClassOrLabel, string $column): string
    {
        /** @var DefaultModel $modelClassOrLabel */
        return class_exists($modelClassOrLabel) && method_exists($modelClassOrLabel, 'getTableFields')
            ? (Hlp::castToString($modelClassOrLabel::getTableFields()[$column] ?? $column))
            : ($modelClassOrLabel ?: $column);
    }
}
