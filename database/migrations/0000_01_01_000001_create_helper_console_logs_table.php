<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ConsoleLog
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
            Schema::connection($connection)->create($table, function (Blueprint $table) {
                $table->id();
    
                $table->uuid('uuid')->nullable(false)->index()
                    ->comment('Uuid консольной команды');
    
                $table->string('name')->nullable(false)->index()
                    ->comment('Название консольной команды');
                $table->string('command')->nullable(false)->index()
                    ->comment('Название класса консольной команды');
                $table->text('cli')->nullable(false)
                    ->comment('Консольная команда');
                $table->longText('output')->nullable(true)
                    ->comment('Вывод консольной команды');
                $table->integer('result')->nullable(true)
                    ->comment('Результат выполнения консольной команды');
                $table->longText('exception')->nullable(true)
                    ->comment('Исключение консольной команды');
                $table->enum('status', ConsoleLogStatusEnum::enumValues())->nullable(false)->index()
                    ->default(ConsoleLogStatusEnum::enumDefault())
                    ->comment('Статус выполнения консольной команды');
                $table->decimal('duration', 10, 3)->nullable(true)
                    ->comment('Время выполнения команды');
                $table->unsignedBigInteger('memory')->nullable(true)
                    ->comment('Потребляемая память при выполнении команды');
                $table->jsonb('info')->nullable(true)
                    ->comment('Информация о выполнении консольной команды');
    
                $table->timestamps();
                $table->index(['created_at', 'updated_at']);
    
                $table->comment(ConsoleLog::COMMENT);
            });
        }
    }


    public function down(): void
    {
        $config = ConfigEnum::ConsoleLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
