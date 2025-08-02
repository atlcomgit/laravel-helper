<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Facades;

use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService;
use Illuminate\Support\Facades\Facade;

/**
 * Фасад бота телеграм
 */
class TelegramBot extends Facade
{
    /**
     * @inheritDoc
     * @see parent::getFacadeAccessor()
     */
    protected static function getFacadeAccessor()
    {
        return TelegramBotService::class;
    }
}
