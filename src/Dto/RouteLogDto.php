<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\RouteLogJob;
use Atlcom\LaravelHelper\Models\RouteLog;

/**
 * @internal
 * Dto лога роута
 * @see RouteLog
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
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::RouteLog, 'queue_dispatch_sync') ?? (isLocal() || isDev() || isTesting()))
                ? RouteLogJob::dispatchSync($this)
                : RouteLogJob::dispatch($this);
        }

        return $this;
    }
}
