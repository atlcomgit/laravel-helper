<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Listeners;

use Atlcom\LaravelHelper\Defaults\DefaultListener;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Services\HttpLogService;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * @see \Illuminate\Http\Client\Events\RequestSending
 */
class HttpRequestSendingListener extends DefaultListener
{
    public function __construct(
        private HttpLogService $httpLogService,
        private LaravelHelperService $laravelHelperService,
    ) {}


    public function __invoke(object $event): void
    {
        !(
            !lhConfig(ConfigEnum::HttpLog, 'only_response')
            && ($dto = HttpLogDto::createByRequest(
                uuid: ($event->request?->header(HttpLogService::HTTP_HEADER_UUID) ?? [])[0] ?? null,
                request: $event->request,
            ))->uuid
        ) ?: $dto->dispatch();
    }
}
