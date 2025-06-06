<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.http_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.http_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid запроса');

            $userTableName = config('laravel-helper.http_log.user.table_name');
            $userPrimaryKeyName = config('laravel-helper.http_log.user.primary_key');
            $userPrimaryKeyType = config('laravel-helper.http_log.user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index()
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('name')->nullable(true)->index();
            $table->enum('type', HttpLogTypeEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogTypeEnum::getDefault())
                ->comment('Тип запроса');
            $table->enum('method', HttpLogMethodEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogMethodEnum::getDefault())
                ->comment('Метод запроса');
            $table->enum('status', HttpLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogStatusEnum::getDefault())
                ->comment('Статус запроса');
            $table->string('ip')->nullable(true)->index()
                ->comment('Ip адрес запроса');
            $table->longText('url')->nullable(false)
                ->comment('Url запроса');
            $table->jsonb('request_headers')->nullable(true)
                ->comment('Заголовки запроса');
            $table->longText('request_data')->nullable(true)
                ->comment('Тело запроса');
            $table->string('request_hash')->nullable(false)->index()
                ->comment('Хеш запроса');
            $table->integer('response_code')->nullable(true)->index()
                ->comment('Код ответа на запрос');
            $table->string('response_message')->nullable(true)
                ->comment('Сообщение ответа на запрос');
            $table->jsonb('response_headers')->nullable(true)
                ->comment('Заголовки ответа на запрос');
            $table->longText('response_data')->nullable(true)
                ->comment('Тело ответа на запрос');
            $table->integer('try_count')->nullable(true)->default(0)
                ->comment('Количество попыток запроса');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о запросе');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог http запросов');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.http_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.http_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
