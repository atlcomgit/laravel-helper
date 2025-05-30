<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.console_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.console_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid консольной команды');

            $table->string('class')->nullable(false)->index()
                ->comment('Класс консольной команды');
            $table->string('name')->nullable(false)->index()
                ->comment('Название консольной команды');
            $table->text('command')->nullable(false)
                ->comment('Консольная команда');
            $table->longText('output')->nullable(true)
                ->comment('Вывод консольной команды');
            $table->integer('result')->nullable(true)
                ->comment('Результат выполнения консольной команды');
            $table->enum('status', ConsoleLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(ConsoleLogStatusEnum::getDefault())
                ->comment('Статус выполнения консольной команды');
            $table->longText('exception')->nullable(true)
                ->comment('Исключение консольной команды');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении консольной команды');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог консольных команд');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.console_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.console_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
