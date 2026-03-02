<?php

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Controllers\TelegramBotController;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Middlewares\HttpLogMiddleware;
use Atlcom\LaravelHelper\Middlewares\IpBlockMiddleware;
use Illuminate\Support\Facades\Route;


if (Lh::config(ConfigEnum::TelegramBot, 'enabled')) {
    $webhook = Hlp::urlParse((string)Lh::config(ConfigEnum::TelegramBot, 'webhook'));
    $webhookUrl = $webhook['path'] ?? '';

    // Регистрация роута вебхука от бота телеграм
    !$webhookUrl ?: Route::post($webhookUrl, [TelegramBotController::class, 'webhook'])
        ->middleware(['api', HttpLogMiddleware::class])
        ->withoutMiddleware([IpBlockMiddleware::class]);
}
