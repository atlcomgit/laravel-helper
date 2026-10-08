<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

/**
 * Переопределённое соединение MariaDB с поддержкой кеширования и логирования запросов
 */
class MariaDbConnection extends \Illuminate\Database\MariaDbConnection
{
    use ConnectionTrait;
}
