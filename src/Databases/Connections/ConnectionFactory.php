<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

/**
 * Фабрика подключений к базе данных
 */
class ConnectionFactory extends \Illuminate\Database\Connectors\ConnectionFactory
{
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        return match ($driver) {
            'mysql' => new MySqlConnection($connection, $database, $prefix, $config),
            'pgsql' => new PostgresConnection($connection, $database, $prefix, $config),
            'sqlserver' => new SqlServerConnection($connection, $database, $prefix, $config),
            'sqlite' => new SQLiteConnection($connection, $database, $prefix, $config),

            default => parent::createConnection($driver, $connection, $database, $prefix, $config),
        };
    }
}
