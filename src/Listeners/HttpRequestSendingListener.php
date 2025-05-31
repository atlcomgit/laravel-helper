<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * @see \Illuminate\Http\Client\Events\RequestSending
 */
class HttpRequestSendingListener
{
    public function __construct(
        private HttpLogService $httpLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    public function __invoke(object $event): void
    {
        !(
            !config('laravel-helper.http_log.only_response')
            && ($dto = HttpLogDto::createByRequest(
                uuid: ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null,
                request: $event->request,
            ))->uuid
            && $this->laravelHelperService->checkExclude('laravel-helper.http_log.out.exclude', $dto->toArray())
        ) ?: HttpLogJob::dispatch($dto);
    }
}
