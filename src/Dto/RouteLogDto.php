<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Jobs\RouteLogJob;
use Atlcom\LaravelHelper\Models\RouteLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;

/**
 * Dto лога роута
 */
class RouteLogDto extends Dto
{
    public string $method;
    public string $uri;
    public ?string $controller;
    public int $count;
    public bool $exist;


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
            'count' => 0,
            'exist' => true,
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
        return RouteLog::getModelCasts();
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
        $this->onlyKeys(RouteLog::getModelKeys())
            ->onlyNotNull();
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            config('laravel-helper.route_log.queue_dispatch_sync')
                ? RouteLogJob::dispatchSync($this)
                : RouteLogJob::dispatch($this);
        }
    }
}
