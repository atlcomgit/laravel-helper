<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Override;

/**
 * Dto настройки разрешений таблицы CRUD модели
 */
class TablePermissionsDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public TablePermissionOptionsDto $create;
    public TablePermissionOptionsDto $update;
    public TablePermissionOptionsDto $patch;
    public TablePermissionOptionsDto $delete;
    public TablePermissionOptionsDto $sort;
    public TablePermissionOptionsDto $resize;
    public TablePermissionOptionsDto $filter;
    public TablePermissionOptionsDto $view;
    public array $custom;


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function defaults(): array
    {
        return [
            'create' => TablePermissionOptionsDto::create(),
            'update' => TablePermissionOptionsDto::create(),
            'patch' => TablePermissionOptionsDto::create(),
            'delete' => TablePermissionOptionsDto::create(),
            'sort' => TablePermissionOptionsDto::create(),
            'resize' => TablePermissionOptionsDto::create(),
            'filter' => TablePermissionOptionsDto::create(),
            'view' => TablePermissionOptionsDto::create(),
            'custom' => [],
        ];
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
