<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Facades;

use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Illuminate\Support\Facades\Facade;

/**
 * Фасад хелпера
 */
class Lh extends Facade
{
    /**
     * @inheritDoc
     * @see parent::getFacadeAccessor()
     */
    protected static function getFacadeAccessor()
    {
        return LaravelHelperService::class;
    }
}
