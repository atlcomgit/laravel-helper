<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;

/**
 * DTO для загрузки файла из Telegram
 */
class TelegramBotOutDownloadFileDto extends TelegramBotOutDto
{
    public string $fileId;
    public string $savePath;
    public ?string $downloadedFilePath;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'fileId' => '',
            'savePath' => '',
            'downloadedFilePath' => null,
            'syncDownload' => false,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'fileId' => 'file_id',
            'savePath' => 'save_path',
            'downloadedFilePath' => 'downloaded_file_path',
            'syncDownload' => 'sync_download',
        ];
    }


    /**
     * Устанавливает идентификатор файла
     *
     * @param string $fileId
     * @return static
     */
    public function setFileId(string $fileId): static
    {
        $this->fileId = $fileId;

        return $this;
    }


    /**
     * Устанавливает путь для сохранения файла
     *
     * @param string $savePath
     * @return static
     */
    public function setSavePath(string $savePath): static
    {
        $this->savePath = $savePath;

        return $this;
    }


    /**
     * Устанавливает путь загруженного файла
     *
     * @param string $downloadedFilePath
     * @return static
     */
    public function setDownloadedFilePath(string $downloadedFilePath): static
    {
        $this->downloadedFilePath = $downloadedFilePath;

        return $this;
    }


    /**
     * Устанавливает флаг синхронной загрузки
     *
     * @param bool $syncDownload
     * @return static
     */
    public function setSyncDownload(bool $syncDownload): static
    {
        $this->syncDownload = $syncDownload;

        return $this;
    }
}
