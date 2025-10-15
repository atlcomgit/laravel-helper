<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Override;

/**
 * Dto настройки пагинации таблицы CRUD модели
 */
class TablePaginationDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public array $countOnPage;
    public bool $visible;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'countOnPage' => 10,
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
