<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see TelegramBotUser
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'user')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->nullable(false)->index()
                ->comment('Uuid пользователя телеграм бота');
            $table->bigInteger('external_user_id')->nullable(false)->index()
                ->comment('Внешний Id пользователя телеграм бота');

            $table->string('first_name')->nullable(false)->index()
                ->comment('Имя пользователя телеграм бота');
            $table->string('user_name')->nullable(false)->index()
                ->comment('Логин пользователя телеграм бота');
            $table->string('phone')->nullable(true)->index()
                ->comment('Телефон пользователя телеграм бота');
            $table->string('language')->nullable(true)->index()
                ->comment('Код локализации пользователя телеграм бота');
            $table->boolean('is_ban')->nullable(false)->index()
                ->comment('Флаг бана пользователя телеграм бота');
            $table->boolean('is_bot')->nullable(true)->index()
                ->comment('Флаг бота');
            $table->jsonb('info')->nullable(true)
                ->comment('Информация о пользователе телеграм бота');

            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');

            $table->comment(TelegramBotUser::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::TelegramBot;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config, 'user')
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
