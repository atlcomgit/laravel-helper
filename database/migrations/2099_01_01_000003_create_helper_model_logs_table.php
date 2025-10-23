<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Services\MigrationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ModelLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::ModelLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            app(MigrationService::class)->addForeignUser($table);

            $table->string('model_type')->nullable(false)->index()
                ->comment('Класс логируемой модели');
            $table->string('model_id')->nullable(true)->index()
                ->comment('id логируемой записи');
            $table->enum('type', ModelLogTypeEnum::enumValues())->nullable(false)->index()
                ->default(ModelLogTypeEnum::enumDefault())
                ->comment('Тип логирования');
            $table->jsonb('attributes')->nullable(false)
                ->comment('Текущие атрибуты логируемой записи');
            $table->jsonb('changes')->nullable(true)
                ->comment('Измененные атрибуты логируемой записи');
            $table->timestamp('created_at')->nullable(false)->default(now())
                ->comment('Дата создания записи лога');

            $table->comment(ModelLog::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::ModelLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
