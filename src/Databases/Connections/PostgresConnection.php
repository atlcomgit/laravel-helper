<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Databases\Connections;

use Atlcom\LaravelHelper\Traits\ConnectionTrait;

/**
 * Переопределённое соединение PostgreSQL с поддержкой логирования и кеша
 */
class PostgresConnection extends \Illuminate\Database\PostgresConnection
{
    use ConnectionTrait;


    /**
     * Возвращает идентификатор последней вставленной записи
     *
     * @return string|int|null
     */
    public function getLastInsertId(): string|int|null
    {
        try {
            // Для PostgreSQL PDO::lastInsertId() без имени последовательности не работает надёжно.
            // Используем LASTVAL() — возвращает последнее значение последовательности текущей сессии.
            $id = $this->getPdo()->query('SELECT LASTVAL()')->fetchColumn();

            return $id ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
