<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

/**
 * Переопределённое соединение MySQL с поддержкой кеширования и логирования запросов
 */
class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    use ConnectionTrait;
}
