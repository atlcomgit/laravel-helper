<?php

declare(strict_types=1);

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see \Atlcom\LaravelHelper\Models\RouteLog
 */
return new class extends Migration {
    public function up(): void
    {
        $config = ConfigEnum::RouteLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->string('method')->nullable(false)->index()
                ->comment('Метод роута');
            $table->longText('uri')->nullable(false)
                ->comment('Uri роута');
            $table->string('controller')->nullable(true)
                ->comment('Контроллер роута');
            $table->unsignedBigInteger('count')->nullable(false)->default(0)
                ->comment('Количество запросов роута');
            $table->boolean('exist')->nullable(false)->default(true)
                ->comment('Флаг существования роута');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);
            // $table->unique(['method', 'uri']);

            $table->comment(RouteLog::COMMENT);
        });
    }


    public function down(): void
    {
        $config = ConfigEnum::RouteLog;
        $connection = Lh::getConnection($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");
        $table = Lh::getTable($config)
            ?? throw new Exception("Не указан параметр в конфиге: {$config->value}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
