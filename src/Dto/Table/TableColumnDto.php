<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Override;

/**
 * Dto настройки колонок таблицы CRUD модели
 */
class TableColumnDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public string $column;
    public string $label;
    public bool $resize = true;
    public bool $sortable = true;
    public bool $visible = true;
    public ?int $minWith = 100;
    public ?int $maxWith = 300;


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyNotNull();
    }
}
