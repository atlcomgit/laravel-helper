<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\LaravelHelper\Models\RouteLog;

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
}
