<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

/**
 * Переопределённое соединение SQLite с поддержкой кеширования и логирования запросов
 */
class SQLiteConnection extends \Illuminate\Database\SQLiteConnection
{
    use ConnectionTrait;
}
