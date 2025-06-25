<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ModelLog
 */
return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.model_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.model_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $userTableName = config('laravel-helper.model_log.user.table_name');
            $userPrimaryKeyName = config('laravel-helper.model_log.user.primary_key');
            $userPrimaryKeyType = config('laravel-helper.model_log.user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('model_type')->nullable(false)->index()
                ->comment('Класс логируемой модели');
            $table->string('model_id')->nullable(true)->index()
                ->comment('id логируемой записи');
            $table->enum('type', ModelLogTypeEnum::enumValues())->nullable(false)->index()
                ->default(ModelLogTypeEnum::getDefault())
                ->comment('Тип логирования');
            $table->jsonb('attributes')->nullable(false)
                ->comment('Текущие атрибуты логируемой записи');
            $table->jsonb('changes')->nullable(true)
                ->comment('Измененные атрибуты логируемой записи');
            $table->timestamp('created_at')->nullable(false)->default(now())
                ->comment('Дата создания записи лога');

            $table->comment('Лог моделей');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.model_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.model_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
