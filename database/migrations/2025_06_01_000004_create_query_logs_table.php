<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\QueryLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::QueryLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid query запроса');

            $userTableName = Lh::config(ConfigEnum::QueryLog, 'user.table_name');
            $userPrimaryKeyName = Lh::config(ConfigEnum::QueryLog, 'user.primary_key');
            $userPrimaryKeyType = Lh::config(ConfigEnum::QueryLog, 'user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('name')->nullable(true)->index()
                ->comment('Название query запроса');
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
            $table->decimal('duration', 10, 3)->nullable(true)
                ->comment('Время выполнения query запроса');
            $table->unsignedBigInteger('memory')->nullable(true)
                ->comment('Потребляемая память при выполнении query запроса');
            $table->unsignedBigInteger('count')->nullable(true)
                ->comment('Количество затронутых записей при выполнении query запроса');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении query запроса');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог query запросов');
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::QueryLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
