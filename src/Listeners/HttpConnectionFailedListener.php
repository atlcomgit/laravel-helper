<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * @see \Illuminate\Http\Client\Events\ConnectionFailed
 */
class HttpConnectionFailedListener extends DefaultListener
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
        ))->uuid ?: $dto->dispatch();
    }
}
