<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

class PostgresConnection extends \Illuminate\Database\PostgresConnection
{
    use ConnectionTrait;
}
