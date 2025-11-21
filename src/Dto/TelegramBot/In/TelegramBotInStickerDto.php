<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\In;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

/**
 * DTO стикера входящего сообщения телеграм
 */
class TelegramBotInStickerDto extends DefaultDto
{
    public string                 $fileId;
    public string                 $fileUniqueId;
    public ?int                   $width;
    public ?int                   $height;
    public ?int                   $fileSize;
    public ?string                $emoji;
    public ?string                $setName;
    public bool                   $isAnimated;
    public bool                   $isVideo;
    public ?string                $type;
    public ?TelegramBotInPhotoDto $thumbnail;
    public ?TelegramBotInPhotoDto $thumb;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'fileId'       => 'string',
            'fileUniqueId' => 'string',
            'width'        => 'integer',
            'height'       => 'integer',
            'fileSize'     => 'integer',
            'emoji'        => 'string',
            'setName'      => 'string',
            'isAnimated'   => 'boolean',
            'isVideo'      => 'boolean',
            'type'         => 'string',
            'thumbnail'    => TelegramBotInPhotoDto::class,
            'thumb'        => TelegramBotInPhotoDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            'isAnimated' => false,
            'isVideo'    => false,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'fileId'       => 'file_id',
            'fileUniqueId' => 'file_unique_id',
            'fileSize'     => 'file_size',
            'setName'      => 'set_name',
            'isAnimated'   => 'is_animated',
            'isVideo'      => 'is_video',
        ];
    }
}
