<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Dto;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Подключение dto как запрос
 */
class LaravelHelperDtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving(Dto::class, function (Dto $dto, Application $app) {
            return $dto->fillFromRequest(request()->toArray());
        });
    }
}
