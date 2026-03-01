<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция: удаление CHECK-ограничения статуса в helper_console_logs
 *
 * Удаляет ограничение helper_console_logs_status_check в PostgreSQL,
 * чтобы не блокировать добавление новых статусов enum на уровне приложения.
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::ConsoleLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        if (!Schema::connection($connection)->hasTable($table)) {
            return;
        }

        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return;
        }

        DB::connection($connection)->statement(
            "ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS helper_console_logs_status_check",
        );
    }


    public function down(): void
    {
        $config = ConfigEnum::ConsoleLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        if (!Schema::connection($connection)->hasTable($table)) {
            return;
        }

        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return;
        }

        $allowedStatuses = implode(
            ', ',
            array_map(
                static fn (string $status) => "'{$status}'",
                ConsoleLogStatusEnum::enumValues(),
            ),
        );

        DB::connection($connection)->statement(
            "ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS helper_console_logs_status_check",
        );

        DB::connection($connection)->statement(
            "ALTER TABLE {$table} ADD CONSTRAINT helper_console_logs_status_check CHECK (status::text = ANY (ARRAY[{$allowedStatuses}]::text[]))",
        );
    }


};
