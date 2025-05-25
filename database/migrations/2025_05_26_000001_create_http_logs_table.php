<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public const TABLE = 'http_logs';


    public function up(): void
    {
        Schema::dropIfExists(self::TABLE);

        Schema::create(self::TABLE, static function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->index()
                ->comment('Uuid запроса');

            $table->foreignUuid('user_id')->nullable(true)->index()
                ->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('restrict')
                ->comment('Uuid пользователя');

            $table->string('name')->nullable(true)->index();
            $table->enum('type', HttpLogTypeEnum::enumValues())->nullable(false)->index()
                ->comment('Тип запроса');
            $table->enum('method', HttpLogMethodEnum::enumValues())->nullable(false)->index()
                ->comment('Метод запроса');
            $table->enum('status', HttpLogStatusEnum::enumValues())->nullable(false)->index()
                ->comment('Статус запроса');
            $table->longText('url')->nullable(false)
                ->comment('Url запроса');
            $table->jsonb('request_headers')->nullable(true)
                ->comment('Заголовки запроса');
            $table->longText('request_data')->nullable(true)
                ->comment('Тело запроса');
            $table->string('request_hash')->nullable(false)->index()
                ->comment('Хеш запроса');
            $table->integer('response_code')->nullable(true)
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
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }


};
