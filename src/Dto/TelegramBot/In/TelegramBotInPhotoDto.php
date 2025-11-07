<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInPhotoDto extends DefaultDto
{
    public string $fileId;
    public string $fileUniqueId;
    public int $fileSize;
    public ?int $width;
    public ?int $height;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'fileId' => 'string',
            'fileUniqueId' => 'string',
            'fileSize' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'fileId' => 'file_id',
            'fileUniqueId' => 'file_unique_id',
            'fileSize' => 'file_size',
        ];
    }
}
