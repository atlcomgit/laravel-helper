<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

/**
 * Переопределённое соединение SQL Server с поддержкой кеширования и логирования запросов
 */
class SqlServerConnection extends \Illuminate\Database\SqlServerConnection
{
    use ConnectionTrait;
}
