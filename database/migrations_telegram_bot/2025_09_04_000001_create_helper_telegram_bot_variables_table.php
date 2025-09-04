<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotVariableTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see TelegramBotVariable
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'variable')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) use ($config) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid переменной телеграм бота');

            $table->foreignId('telegram_bot_chat_id')->nullable(false)->index()
                ->comment('Связь с пользователем телеграм бота')
                ->references('id')->on(Lh::getTable($config, 'chat'))
                ->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('telegram_bot_message_id')->nullable(true)->index()
                ->comment('Связь с цитируемым сообщением телеграм бота (replyTo)')
                ->references('id')->on(Lh::getTable($config, 'message'))
                ->onUpdate('cascade')->onDelete('restrict');

            $table->enum('type', TelegramBotVariableTypeEnum::enumValues())->nullable(false)->index()
                ->default(TelegramBotVariableTypeEnum::enumDefault())
                ->comment('Тип переменной телеграм бота');

            $table->text('name')->nullable(false)->index()
                ->comment('Имя переменной чата телеграм бота');
            $table->longText('value')->nullable(true)
                ->comment('Значение переменной чата телеграм бота');

            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');

            $table->comment(TelegramBotVariable::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'variable')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
