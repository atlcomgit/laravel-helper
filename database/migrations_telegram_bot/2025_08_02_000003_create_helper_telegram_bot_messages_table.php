<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see TelegramBotMessage
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'message')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) use ($config) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid сообщения телеграм бота');
            $table->bigInteger('external_message_id')->nullable(false)->index()
                ->comment('Внешний Id сообщения телеграм бота');
            $table->bigInteger('external_update_id')->nullable(true)->index()
                ->comment('Внешний Id обновления сообщения телеграм бота');

            $table->foreignId('telegram_bot_chat_id')->nullable(false)->index()
                ->references('id')->on(Lh::getTable($config, 'chat'))
                ->onUpdate('cascade')->onDelete('restrict')
                ->comment('Связь с пользователем телеграм бота');
            $table->foreignId('telegram_bot_user_id')->nullable(false)->index()
                ->references('id')->on(Lh::getTable($config, 'user'))
                ->onUpdate('cascade')->onDelete('restrict')
                ->comment('Связь с чатом телеграм бота');
            $table->foreignId('telegram_bot_message_id')->nullable(true)->index()
                ->references('id')->on(Lh::getTable($config, 'message'))
                ->onUpdate('cascade')->onDelete('restrict')
                ->comment('Связь с цитируемым сообщением телеграм бота (replyTo)');

            $table->longText('text')->nullable(false)
                ->comment('Текст сообщения чата телеграм бота');
            $table->dateTime('send_at')->nullable(false)->index()
                ->comment('Дата и время сообщения телеграм бота');
            $table->dateTime('edit_at')->nullable(true)->index()
                ->comment('Дата и время редактирования сообщения телеграм бота');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о сообщении телеграм бота');

            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');

            $table->comment(TelegramBotMessage::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'message')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
