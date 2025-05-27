<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public const TABLE = 'model_logs';
    public const COMMENT = 'Лог моделей';


    public function up(): void
    {
        $connection = config('laravel-helper.model_log.connection');
        $table = config('laravel-helper.model_log.table');

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->comment(self::COMMENT);

            $table->id();

            $userTableName = config('laravel-helper.http_log.user.table_name');
            $userPrimaryKeyName = config('laravel-helper.http_log.user.primary_key');
            $userPrimaryKeyType = config('laravel-helper.http_log.user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index()
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
        $connection = config('laravel-helper.model_log.connection');
        $table = config('laravel-helper.model_log.table');

        Schema::connection($connection)->dropIfExists($table);
    }


};
