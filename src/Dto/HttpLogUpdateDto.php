<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dto обновления лога http запроса
 */
class HttpLogUpdateDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public string $uuid;

    public ?HttpLogStatusEnum $status;
    public ?int $responseCode;
    public ?string $responseMessage;
    public ?array $responseHeaders;
    public ?string $responseData;
    public ?float $duration;
    public ?int $size;
    public ?array $info;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'uuid' => uuid(),
        ];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return HttpLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::mappings()
     *
     * @return array
     */
    // #[Override()]
    protected function mappings(): array
    {
        return [
            'uuid' => 'request_headers.' . HttpLogService::HTTP_HEADER_UUID . '.0',
        ];
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilled(array $array): void
    {
        !$this->responseMessage ?: $this->responseMessage = Str::substr($this->responseMessage, 0, 255);
        !$this->responseData ?: $this->responseData = mb_convert_encoding($this->responseData, 'utf-8');
    }


    /**
     * @inheritDoc
     * @see parent::onSerializing()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(HttpLog::getModelKeys())
            ->onlyNotNull()
            ->includeArray([
                'try_count' => DB::raw('try_count + 1'),
            ]);
    }
}
