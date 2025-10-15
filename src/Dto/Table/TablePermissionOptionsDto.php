<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Override;

/**
 * Dto настройки опций разрешения таблицы CRUD модели
 */
class TablePermissionOptionsDto extends DefaultDto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public bool $enabled;
    public bool $visible;


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function defaults(): array
    {
        return [
            'enabled' => true,
            'visible' => true,
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
