<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\HttpLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\HttpLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::HttpLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid http запроса');

            $userTableName = Lh::config(ConfigEnum::HttpLog, 'user.table_name');
            $userPrimaryKeyName = Lh::config(ConfigEnum::HttpLog, 'user.primary_key');
            $userPrimaryKeyType = Lh::config(ConfigEnum::HttpLog, 'user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('name')->nullable(true)->index();
            $table->enum('type', HttpLogTypeEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogTypeEnum::getDefault())
                ->comment('Тип http запроса');
            $table->enum('method', HttpLogMethodEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogMethodEnum::getDefault())
                ->comment('Метод http запроса');
            $table->enum('status', HttpLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(HttpLogStatusEnum::getDefault())
                ->comment('Статус http запроса');
            $table->string('ip')->nullable(true)->index()
                ->comment('Ip адрес http запроса');
            $table->longText('url')->nullable(false)
                ->comment('Url http запроса');
            $table->jsonb('request_headers')->nullable(true)
                ->comment('Заголовки http запроса');
            $table->longText('request_data')->nullable(true)
                ->comment('Тело http запроса');
            $table->string('request_hash')->nullable(false)->index()
                ->comment('Хеш http запроса');
            $table->integer('response_code')->nullable(true)->index()
                ->comment('Код ответа на http запрос');
            $table->string('response_message')->nullable(true)
                ->comment('Сообщение ответа на http запрос');
            $table->jsonb('response_headers')->nullable(true)
                ->comment('Заголовки ответа на http запрос');
            $table->longText('response_data')->nullable(true)
                ->comment('Тело ответа на http запрос');
            $table->string('cache_key')->nullable(true)->index()
                ->comment('Ключ кеша http запроса');
            $table->boolean('is_cached')->nullable(false)
                ->comment('Флаг сохранения http запроса в кеш');
            $table->boolean('is_from_cache')->nullable(false)
                ->comment('Флаг обращения http запроса в кеш');
            $table->integer('try_count')->nullable(true)->default(0)
                ->comment('Количество попыток http запроса');
            $table->decimal('duration', 10, 3)->nullable(true)
                ->comment('Время выполнения http запроса');
            $table->unsignedBigInteger('size')->nullable(true)
                ->comment('Размер http запроса');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о http запросе');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment(HttpLog::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::HttpLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
