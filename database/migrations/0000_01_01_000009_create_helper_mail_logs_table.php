<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\MailLog;
use Atlcom\LaravelHelper\Services\MigrationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\MailLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::MailLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        if (!Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->create($table, function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')->nullable(false)->index()
                    ->comment('Uuid письма');

                app(MigrationService::class)->addForeignUser($table);

                $table->enum('status', MailLogStatusEnum::enumValues())->nullable(false)->index()
                    ->default(MailLogStatusEnum::enumDefault())
                    ->comment('Статус отправки');

                $table->string('from')->nullable(true)->index()
                    ->comment('Отправитель');
                $table->jsonb('to')->nullable(true)->index()
                    ->comment('Получатели');
                $table->jsonb('cc')->nullable(true)
                    ->comment('Копии');
                $table->jsonb('bcc')->nullable(true)
                    ->comment('Скрытые копии');
                $table->string('subject')->nullable(true)->index()
                    ->comment('Тема письма');
                $table->longText('body')->nullable(true)
                    ->comment('Тело письма');
                $table->jsonb('attachments')->nullable(true)
                    ->comment('Вложения');

                $table->text('error_message')->nullable(true)
                    ->comment('Сообщение об ошибке');

                $table->jsonb('info')->nullable(true)
                    ->comment('Дополнительная информация');

                $table->timestamps();
                $table->index(['created_at', 'updated_at']);

                $table->comment(MailLog::COMMENT);
            });
        }
    }


    public function down(): void
    {
        $config = ConfigEnum::MailLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
