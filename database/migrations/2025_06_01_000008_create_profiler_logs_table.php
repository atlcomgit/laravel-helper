<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ProfilerLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::ProfilerLog;
        $connection = LaravelHelperService::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = LaravelHelperService::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid профилирования метода класса');

            $table->string('class')->nullable(false)->index()
                ->comment('Название класса профилирования');
            $table->string('method')->nullable(false)->index()
                ->comment('Название метода профилирования');
            $table->boolean('is_static')->nullable(false)
                ->comment('Флаг статичного вызова метода');
            $table->jsonb('arguments')->nullable(true)
                ->comment('Аргументы метода');
            $table->enum('status', ProfilerLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(ProfilerLogStatusEnum::getDefault())
                ->comment('Статус выполнения метода');
            $table->longText('result')->nullable(true)
                ->comment('Результат метода');
            $table->longText('exception')->nullable(true)
                ->comment('Исключение метода');
            $table->unsignedBigInteger('count')->nullable(false)->default(0)
                ->comment('Количество вызовов метода');
            $table->decimal('duration', 10, 3)->nullable(true)
                ->comment('Время выполнения метода');
            $table->unsignedBigInteger('memory')->nullable(true)
                ->comment('Потребляемая память при выполнении метода');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении метода');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог профилирования метода класса');
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::ProfilerLog;
        $connection = LaravelHelperService::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = LaravelHelperService::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
