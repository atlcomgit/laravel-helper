<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.view_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.view_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid рендеринга blade шаблона');

            $table->string('name')->nullable(false)->index()
                ->comment('Название blade шаблона');
            $table->jsonb('data')->nullable(true)
                ->comment('Данные рендеринга blade шаблона');
            $table->jsonb('merge_data')->nullable(true)
                ->comment('Объединение данных рендеринга blade шаблона');
            $table->longText('render')->nullable(true)
                ->comment('Результат рендеринга blade шаблона');
            $table->string('cache_key')->nullable(true)->index()
                ->comment('Ключ кеша query запроса');
            $table->boolean('is_cached')->nullable(false)
                ->comment('Флаг сохранения рендеринга blade шаблона в кеш');
            $table->boolean('is_from_cache')->nullable(false)
                ->comment('Флаг обращения рендеринга blade шаблона в кеш');
            $table->enum('status', ViewLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(ViewLogStatusEnum::getDefault())
                ->comment('Статус выполнения рендеринга blade шаблона');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении рендеринга blade шаблона');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог рендеринга blade шаблона');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.query_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.query_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
