<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Illuminate\Support\Collection;
use Override;

/**
 * Dto для настройки таблицы CRUD модели
 */
class TableDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public string $model;
    public TablePermissionsDto $permissions;
    public TablePaginationDto $pagination;
    /** @var Collection<TableColumnDto> */
    public Collection $columns;
    /** @var Collection<TableFilterDto> */
    public Collection $filters;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'permissions' => TablePermissionsDto::class,
            'pagination' => TablePaginationDto::class,
            'columns' => [TableColumnDto::class],
            'filters' => [TableFilterDto::class],
        ];
    }


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function defaults(): array
    {
        return [
            'permissions' => TablePermissionsDto::create(),
            'pagination' => TablePaginationDto::create(),
            'filters' => [],
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onFilling(array &$array): void
    {
        if (($model = $this->model ?? $array['model'] ?? null) && !($array['columns'] ?? null)) {
            $array['columns'] = collect($model::getTableFields())
                ->map(static fn (string $column, $label) => TableColumnDto::create(
                    column: $column,
                    label: $label,
                ));
        }
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
