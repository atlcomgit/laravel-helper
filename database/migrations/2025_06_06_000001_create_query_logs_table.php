<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.query_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.query_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid query запроса');

            $table->string('model_type')->nullable(true)->index()
                ->comment('Класс модели query запроса');
            $table->string('model_id')->nullable(true)->index()
                ->comment('id записи query запроса');
            $table->longText('query')->nullable(false)
                ->comment('Сырой query запрос');
            $table->string('cache_key')->nullable(true)->index()
                ->comment('Ключ кеша query запроса');
            $table->boolean('is_cached')->nullable(false)
                ->comment('Флаг сохранения query запроса в кеш');
            $table->boolean('is_from_cache')->nullable(false)
                ->comment('Флаг обращения query запроса в кеш');
            $table->enum('status', QueryLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(QueryLogStatusEnum::getDefault())
                ->comment('Статус выполнения query запроса');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении query запроса');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог query запросов');
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
