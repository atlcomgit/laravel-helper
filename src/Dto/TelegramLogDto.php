<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\TelegramLogJob;
use Carbon\Carbon;

/**
 * Dto сообщения в телеграм
 */
class TelegramLogDto extends Dto
{
    public string $chatId;
    public ?string $title;
    public ?string $message;
    public ?string $type;
    public int|string|null $userId;
    public ?string $ip;
    public ?string $uri;
    public ?string $method;
    public ?string $uuid;

    public string $timeStamp;
    public mixed $debugData;


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
            'chatId' => 'chat_id',
            'userId' => 'user_id',
            'timeStamp' => 'time_stamp',
            'debugData' => 'debug_data',
        ];
    }


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
            'ip' => ip(),
            'userId' => user(returnOnlyId: true),
            'method' => static::getMethod(),
            'uri' => static::getUri(),
            'timeStamp' => Carbon::now()->format('Y-m-d H'),
            'debugData' => static::getDebugData(),
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
        return [];
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
        $this->excludeKeys(['timeStamp', 'debugData']);
    }


    /**
     * Возвращает метод запроса
     *
     * @return string
     */
    public static function getMethod(): string
    {
        $argc = (request()->server()['argc'] ?? 0) > 1;

        return (app()->runningInConsole() || $argc) ? 'CLI' : request()->getMethod();
    }


    /**
     * Возвращает URI запроса
     *
     * @return string
     */
    public static function getUri(): string
    {
        $argc = (request()->server()['argc'] ?? 0) > 1;

        return (app()->runningInConsole() || $argc)
            ? implode(' ', request()->server()['argv'] ?? [])
            : request()->getRequestUri();
    }


    /**
     * Возвращает массив debug данных
     *
     * @return array
     */
    public static function getDebugData(): array
    {
        return [
            'uri' => static::getMethod() . ' ' . static::getUri(),
            'config' => [
                'app.debug' => config('app.debug'),
                'app.debug_data' => config('app.debug_data'),
                'app.debug_trace' => config('app.debug_trace'),
                'app.debug_trace_vendor' => config('app.debug_trace_vendor'),
            ],
            ...(
                app()->runningInConsole()
                ? [
                    'arguments' => request()->server()['argv'] ?? null,
                ]
                : [
                    'server' => request()->server(),
                    'cookies' => request()->cookies?->all(),
                    'headers' => request()->headers?->all(),
                    'params' => request()->all(),
                    'files' => request()?->files->all(),
                ]
            ),
            'time' => Carbon::now()->format('d.m.Y в H:i:s'),
        ];
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::TelegramLog, 'queue_dispatch_sync') ?? (isLocal() || isTesting()))
                ? TelegramLogJob::dispatchSync($this)
                : TelegramLogJob::dispatch($this);
        }
    }
}
