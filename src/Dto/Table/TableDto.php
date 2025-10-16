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
    public ?string $name;
    public TablePermissionsDto $permissions;
    public TablePaginationDto $pagination;
    /** @var Collection<TableColumnDto> */
    public Collection $columns;
    /** @var Collection<TableFilterDto> */
    public Collection $filters;


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function casts(): array
    {
        return [
            'model' => 'string',
            'name' => 'string',
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
    #[Override()]
    protected function onFilling(array &$array): void
    {
        $model = $this->model ?? $array['model'] ?? null;

        if ($model && !($this->defaults()['name'] ?? [])) {
            !method_exists($model, 'getTableComment') ?: $array['name'] ??= $model::getTableComment();
        }

        if ($model && !($this->defaults()['columns'] ?? [])) {
            $array['columns'] ??= collect($model::getTableFields())
                ->map(
                    static fn (?string $label, string $column) => match ($column) {
                        'updated_at', 'deleted_at' => TableColumnDto::create(
                            column: $column,
                            label: $label,
                            visible: false,
                        ),

                        default => TableColumnDto::create(column: $column, label: $label),
                    }
                );
        }
    }


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull()->excludeKeys(['model']);
    }


    /**
     * @inheritDoc
     */
    #[Override()]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
