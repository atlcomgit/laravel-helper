<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class TelegramBotInDocumentDto extends DefaultDto
{
    public string $fileId;
    public string $fileUniqueId;
    public int $fileSize;
    public string $fileName;
    public string $mimeType;
    public ?TelegramBotInPhotoDto $thumbnail;
    public ?TelegramBotInPhotoDto $thumb;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'fileId' => 'string',
            'fileUniqueId' => 'string',
            'fileSize' => 'integer',
            'fileName' => 'string',
            'mimeType' => 'string',
            'thumbnail' => TelegramBotInPhotoDto::class,
            'thumb' => TelegramBotInPhotoDto::class,
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
            'fileName' => 'file_name',
            'mimeType' => 'mime_type',
        ];
    }
}
