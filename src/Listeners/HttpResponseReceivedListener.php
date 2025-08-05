<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * @internal
 * @see \Illuminate\Http\Client\Events\ResponseReceived
 */
class HttpResponseReceivedListener extends DefaultListener
{
    public function __construct(
        private HttpLogService $httpLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    public function __invoke(object $event): void
    {
        !($dto = HttpLogDto::createByResponse(
            uuid: ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null,
            request: $event->request,
            response: $event->response,
        ))->uuid ?: $dto->dispatch();
    }
}
