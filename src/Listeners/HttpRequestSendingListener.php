<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\HttpLogService;

/**
 * @see \Illuminate\Http\Client\Events\RequestSending
 */
class HttpRequestSendingListener
{
    public function __construct(private HttpLogService $httpLogService) {}


    public function __invoke(object $event): void
    {
        !(
            !config('laravel-helper.http_log.only_response')
            &&
            ($httpLogDto = HttpLogDto::createByRequest(
                ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null,
                $event->request,
            ))->uuid
        ) ?: HttpLogJob::dispatch($httpLogDto);
    }
}