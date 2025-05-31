<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Посредник логирования входящих http запросов
 */
class HttpLogMiddleware
{
    private static ?string $uuid = null;
    private static ?string $startAt = null;


    public function handle(Request $request, Closure $next)
    {
        $dto = null;
        if (config('laravel-helper.http_log.in.enabled')) {
            static::$startAt = (string)now()->getTimestampMs();
            $dto = HttpLogDto::createByRequest(static::$uuid, $request);
            static::$uuid = app(LaravelHelperService::class)
                ->checkExclude('laravel-helper.http_log.in.exclude', $dto->toArray())
                ? null
                : uuid();
        }

        !(static::$uuid && $dto && !config('laravel-helper.http_log.only_response'))
            ?: HttpLogJob::dispatch($dto);

        return $next($request);
    }


    public function terminate(Request $request, Response $response): void
    {
        !static::$uuid ?: HttpLogJob::dispatch(
            HttpLogDto::createByResponse(static::$uuid, $request, $response, [
                ...(static::$startAt
                    ? [
                        'duration' => Helper::timeSecondsToString(
                            (int)Carbon::createFromTimestampMs(static::$startAt)->diffInMilliseconds() / 1000
                        ),
                    ]
                    : []
                ),

            ]),
        );
    }
}
