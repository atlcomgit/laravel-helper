<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ViewLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::ViewLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid рендеринга blade шаблона');

            $userTableName = Lh::config(ConfigEnum::ViewLog, 'user.table_name');
            $userPrimaryKeyName = Lh::config(ConfigEnum::ViewLog, 'user.primary_key');
            $userPrimaryKeyType = Lh::config(ConfigEnum::ViewLog, 'user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('name')->nullable(false)->index()
                ->comment('Название blade шаблона');
            $table->jsonb('data')->nullable(true)
                ->comment('Данные рендеринга blade шаблона');
            $table->jsonb('merge_data')->nullable(true)
                ->comment('Объединение данных рендеринга blade шаблона');
            $table->longText('render')->nullable(true)
                ->comment('Результат рендеринга blade шаблона');
            $table->string('cache_key')->nullable(true)->index()
                ->comment('Ключ кеша рендеринга blade шаблона');
            $table->boolean('is_cached')->nullable(false)
                ->comment('Флаг сохранения рендеринга blade шаблона в кеш');
            $table->boolean('is_from_cache')->nullable(false)
                ->comment('Флаг обращения рендеринга blade шаблона в кеш');
            $table->enum('status', ViewLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(ViewLogStatusEnum::enumDefault())
                ->comment('Статус выполнения рендеринга blade шаблона');
            $table->decimal('duration', 10, 3)->nullable(true)
                ->comment('Время выполнения рендеринга blade шаблона');
            $table->unsignedBigInteger('memory')->nullable(true)
                ->comment('Потребляемая память при выполнении рендеринга blade шаблона');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении рендеринга blade шаблона');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment(ViewLog::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::ViewLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
