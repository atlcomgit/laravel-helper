<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Repositories\ConsoleLogRepository;
use Illuminate\Database\Connection;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тесты репозитория логирования консольных команд
 */
final class ConsoleLogRepositoryTest extends TestCase
{
    /**
     * Проверяет безопасную сборку выражения конкатенации вывода команды
     *
     * @return void
     */
    #[Test]
    public function outputConcatSqlSanitizesAndQuotesCommandOutput(): void
    {
        $repositoryClass = new ReflectionClass(ConsoleLogRepository::class);
        $repository = $repositoryClass->newInstanceWithoutConstructor();
        $method = $repositoryClass->getMethod('outputConcatSql');
        $method->setAccessible(true);

        $connection = new class(new PDO('sqlite::memory:')) extends Connection {
            public function getDriverName()
            {
                return 'pgsql';
            }
        };

        $sql = $method->invoke(
            $repository,
            $connection,
            "- ebd6188 Merge branch 'alek/TRELLO-32'; DROP TABLE users; --",
        );

        $this->assertStringStartsWith("COALESCE(output, '') || '", $sql);
        $this->assertStringContainsString("Merge branch \\''alek/TRELLO-32\\''", $sql);
        $this->assertStringNotContainsString('DROP TABLE', $sql);
    }
}
