<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция: добавление индексов в таблицу helper_query_logs для аналитики медленных запросов
 *
 * Проблемы, решаемые данной миграцией:
 *
 * 1. Индекс на (duration) — ускоряет выборку медленных запросов: WHERE duration > N
 * 2. Индекс на (memory) — ускоряет выборку запросов с высоким потреблением памяти
 * 3. Составной индекс (created_at, status) — ускоряет команду очистки lh:cleanup:query_log
 *    которая выполняет WHERE created_at <= ? AND status = 'success'
 *
 * @see \Atlcom\LaravelHelper\Commands\QueryLogCleanupCommand
 * @see \Atlcom\LaravelHelper\Repositories\QueryLogRepository::cleanup()
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::QueryLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        if (!Schema::connection($connection)->hasTable($table)) {
            return;
        }

        $schema = Schema::connection($connection);

        if (!$schema->hasIndex($table, 'helper_query_logs_duration_index')) {
            $schema->table($table, static function (Blueprint $table) {
                $table->index(['duration'], 'helper_query_logs_duration_index');
            });
        }

        if (!$schema->hasIndex($table, 'helper_query_logs_memory_index')) {
            $schema->table($table, static function (Blueprint $table) {
                $table->index(['memory'], 'helper_query_logs_memory_index');
            });
        }

        // Составной индекс (status, created_at) для команды очистки через raw SQL
        // Blueprint::index не поддерживает DESC, поэтому используем raw SQL
        if (!$schema->hasIndex($table, 'helper_query_logs_status_created_index')) {
            $connection = DB::connection($connection)->getDriverName() === 'pgsql'
                ? $connection
                : null;

            DB::connection($connection)->statement("
                CREATE INDEX IF NOT EXISTS helper_query_logs_status_created_index
                ON {$table} (status, created_at DESC)
            ");
        }
    }


    public function down(): void
    {
        $config = ConfigEnum::QueryLog;
        $connection = Lh::getConnection($config) ?? null;
        $table = Lh::getTable($config) ?? null;

        if (!$table || !Schema::connection($connection)->hasTable($table)) {
            return;
        }

        Schema::connection($connection)->table($table, static function (Blueprint $table) {
            $table->dropIndexIfExists('helper_query_logs_duration_index');
            $table->dropIndexIfExists('helper_query_logs_memory_index');
        });

        DB::connection($connection)->statement("
            DROP INDEX IF EXISTS helper_query_logs_status_created_index
        ");
    }


};
