<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseIn;
use Illuminate\Http\Client\Response as ResponseOut;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dto кеша http запроса
 */
class HttpCacheDto extends DefaultDto
{
    public array $tags;
    public ?string $key;
    public int|bool|null $ttl;
    public string $requestMethod;
    public string $requestUrl;
    public array|string|null $requestData = null;
    public ResponseIn|ResponseOut|StreamedResponse|BinaryFileResponse|null $response;


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
            'tags' => [],
            'ttl' => (int)Lh::config(ConfigEnum::HttpCache, 'ttl'),
            'response' => null,
        ];
    }
}
