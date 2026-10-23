<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты миграций пакета, которые зависят от возможностей СУБД
 * @see \Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider
 */
final class PackageMigrationsTest extends TestCase
{
    // Индексы таблицы журнала запросов, которые создаёт миграция 0000_01_01_000010
    private const QUERY_LOG_INDEXES = [
        'helper_query_logs_duration_index',
        'helper_query_logs_memory_index',
        'helper_query_logs_status_created_index',
    ];


    protected function getPackageProviders($app)
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'testing');
        $this->app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }


    /**
     * Индекс по JSON-колонке получателей создаётся только в PostgreSQL: MySQL запрещает обычный индекс по JSON
     * @see ../../database/migrations/0000_01_01_000009_create_helper_mail_logs_table.php
     *
     * @return void
     */
    #[Test]
    public function mailLogsMigrationIndexesJsonRecipientsOnlyInPostgres(): void
    {
        $schema = Schema::connection('testing');

        $this->assertTrue($schema->hasTable('helper_mail_logs'));
        $this->assertTrue($schema->hasIndex('helper_mail_logs', 'helper_mail_logs_subject_index'));
        $this->assertFalse($schema->hasIndex('helper_mail_logs', 'helper_mail_logs_to_index'));
    }


    /**
     * Индексы журнала запросов создаются и удаляются повторно без IF [NOT] EXISTS, которых нет в MySQL
     * @see ../../database/migrations/0000_01_01_000010_add_indexes_to_helper_query_logs_table.php
     *
     * @return void
     */
    #[Test]
    public function queryLogIndexesMigrationIsRepeatableAndReversible(): void
    {
        $schema = Schema::connection('testing');
        $migration = require __DIR__ . '/../../database/migrations/'
            . '0000_01_01_000010_add_indexes_to_helper_query_logs_table.php';

        // Индексы уже созданы при подготовке теста: повторный запуск ничего не меняет
        $migration->up();
        $this->assertQueryLogIndexes(true, $schema);

        $migration->down();
        $this->assertQueryLogIndexes(false, $schema);

        // Повторный откат без индексов и повторное создание выполняются без ошибок
        $migration->down();
        $migration->up();
        $this->assertQueryLogIndexes(true, $schema);
    }


    /**
     * Проверяет наличие всех индексов журнала запросов
     *
     * @param bool $exists
     * @param Builder $schema
     * @return void
     */
    private function assertQueryLogIndexes(bool $exists, Builder $schema): void
    {
        foreach (self::QUERY_LOG_INDEXES as $index) {
            $this->assertSame($exists, $schema->hasIndex('helper_query_logs', $index), $index);
        }
    }
}
