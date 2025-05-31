<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * @see \Illuminate\Http\Client\Events\ResponseReceived
 */
class HttpResponseReceivedListener
{
    public function __construct(
        private HttpLogService $httpLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    public function __invoke(object $event): void
    {
        !(
            ($dto = HttpLogDto::createByResponse(
                uuid: ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null,
                request: $event->request,
                response: $event->response,
            ))->uuid
            && $this->laravelHelperService->checkExclude('laravel-helper.http_log.out.exclude', $dto->toArray())
        )
            ?: HttpLogJob::dispatch($dto);
    }
}
