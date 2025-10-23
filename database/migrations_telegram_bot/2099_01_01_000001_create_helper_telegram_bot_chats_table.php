<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see TelegramBotChat
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'chat')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid чата телеграм бота');
            $table->bigInteger('external_chat_id')->nullable(false)->index()
                ->comment('Внешний Id чата телеграм бота');

            $table->string('name')->nullable(false)->index()
                ->comment('Имя чата телеграм бота');
            $table->string('chat_name')->nullable(false)->index()
                ->comment('Логин чата телеграм бота');
            $table->string('type')->nullable(false)->index()
                ->comment('Тип чата телеграм бота');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о чате телеграм бота');

            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');

            $table->comment(TelegramBotChat::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'chat')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
