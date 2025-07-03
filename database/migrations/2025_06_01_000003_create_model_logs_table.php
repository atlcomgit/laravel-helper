<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\ModelLog
 */
return new class extends Migration {
    public ConfigEnum $config = ConfigEnum::ConsoleLog;


    public function up(): void
    {
        $connection = config($config = LaravelHelperService::getConnection($this->config))
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = LaravelHelperService::getTable($this->config))
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) use ($this) {
            $table->id();

            $userTableName = lhConfig($this->config, 'user.table_name');
            $userPrimaryKeyName = lhConfig($this->config, 'user.primary_key');
            $userPrimaryKeyType = lhConfig($this->config, 'user.primary_type');

            if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
                $table->addColumn($userPrimaryKeyType, 'user_id')->nullable(true)->index();
                $table->foreign('user_id')
                    ->references($userPrimaryKeyName)->on($userTableName)
                    ->onUpdate('cascade')->onDelete('restrict')
                    ->comment('Id пользователя');
            }

            $table->string('model_type')->nullable(false)->index()
                ->comment('Класс логируемой модели');
            $table->string('model_id')->nullable(true)->index()
                ->comment('id логируемой записи');
            $table->enum('type', ModelLogTypeEnum::enumValues())->nullable(false)->index()
                ->default(ModelLogTypeEnum::getDefault())
                ->comment('Тип логирования');
            $table->jsonb('attributes')->nullable(false)
                ->comment('Текущие атрибуты логируемой записи');
            $table->jsonb('changes')->nullable(true)
                ->comment('Измененные атрибуты логируемой записи');
            $table->timestamp('created_at')->nullable(false)->default(now())
                ->comment('Дата создания записи лога');

            $table->comment('Лог моделей');
        });
    }


    public function down(): void
    {
        $connection = config($config = LaravelHelperService::getConnection($this->config))
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = LaravelHelperService::getTable($this->config))
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
