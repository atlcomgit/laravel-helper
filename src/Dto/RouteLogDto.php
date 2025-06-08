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
     * @override
     * Возвращает массив значений по умолчанию
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
     * Возвращает массив преобразований типов
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return RouteLog::getModelCasts();
    }


    /**
     * Метод вызывается до преобразования dto в массив
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
        if (
            !config('laravel-helper.route_log.enabled')
            || app(LaravelHelperService::class)->checkIgnoreTables([RouteLog::getTableName()])
            || app(LaravelHelperService::class)
                ->checkExclude('laravel-helper.route_log.exclude', $this->toArray())
        ) {
            return;
        }

        isTesting()
            ? RouteLogJob::dispatchSync($this)
            : RouteLogJob::dispatch($this);
    }
}
