<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Middlewares;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
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
        if (lhConfig(ConfigEnum::HttpLog, 'enabled') && lhConfig(ConfigEnum::HttpLog, 'in.enabled')) {
            static::$startAt = (string)now()->getTimestampMs();
            static::$uuid = uuid();
            $dto = HttpLogDto::createByRequest(static::$uuid, $request);
        }

        !(static::$uuid && $dto && !lhConfig(ConfigEnum::HttpLog, 'only_response'))
            ?: $dto->dispatch();

        return $next($request);
    }


    public function terminate(Request $request, Response $response): void
    {
        !static::$uuid
            ?: HttpLogDto::createByResponse(static::$uuid, $request, $response, [
                'startAt' => static::$startAt,
            ])->dispatch()
        ;
    }
}
