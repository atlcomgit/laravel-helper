<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Table;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Override;

/**
 * Dto настроек таблицы
 */
class TableSettingsDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;
    public const AUTO_MAPPINGS_ENABLED = true;
    public const AUTO_SERIALIZE_ENABLED = true;

    public ?string $title;
    public ?string $slug;
    public ?string $broadcast;


    /**
     * @inheritDoc
     */
    #[Override()]
    protected function casts(): array
    {
        return [
            'title' => 'string',
            'slug' => 'string',
            'broadcast' => 'string',
        ];
    }
}
