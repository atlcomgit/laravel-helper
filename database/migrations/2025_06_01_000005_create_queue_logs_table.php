<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\QueueLog
 */
return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.queue_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.queue_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid очереди');

            $userTableName = config('laravel-helper.queue_log.user.table_name');
            $userPrimaryKeyName = config('laravel-helper.queue_log.user.primary_key');
            $userPrimaryKeyType = config('laravel-helper.queue_log.user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('job_id')->nullable(false)->index()
                ->comment('Идентификатор очереди');
            $table->string('job_name')->nullable(false)
                ->comment('Название класса очереди');
            $table->string('name')->nullable(false)->index()
                ->comment('Название очереди');
            $table->string('connection')->nullable(false)
                ->comment('Подключение очереди');
            $table->string('queue')->nullable(false)
                ->comment('Название очереди');
            $table->longText('payload')->nullable(true)
                ->comment('Полезная нагрузка очереди');
            $table->datetime('delay')->nullable(true)
                ->comment('Задержка выполнения очереди');
            $table->integer('attempts')->nullable(true)
                ->comment('Попытка запуска очереди');
            $table->enum('status', QueueLogStatusEnum::enumValues())->nullable(false)->index()
                ->default(QueueLogStatusEnum::getDefault())
                ->comment('Статус выполнения очереди');
            $table->longText('exception')->nullable(true)
                ->comment('Исключение очереди');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о выполнении очереди');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);

            $table->comment('Лог очередей');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.queue_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.queue_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
