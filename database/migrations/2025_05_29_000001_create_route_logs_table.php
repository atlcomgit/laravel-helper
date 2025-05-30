<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config($config = 'laravel-helper.route_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.route_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->string('method')->nullable(false)->index()
                ->comment('Метод роута');
            $table->longText('uri')->nullable(false)->index()
                ->comment('Uri роута');
            $table->string('controller')->nullable(true)
                ->comment('Контроллер роута');
            $table->unsignedBigInteger('count')->nullable(false)->default(0)
                ->comment('Количество запросов роута');
            $table->boolean('exist')->nullable(false)->default(true)
                ->comment('Флаг существования роута');

            $table->timestamps();
            $table->index(['created_at', 'updated_at']);
            $table->unique(['method', 'uri']);

            $table->comment('Лог роутов');
        });
    }


    public function down(): void
    {
        $connection = config($config = 'laravel-helper.route_log.connection')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");
        $table = config($config = 'laravel-helper.route_log.table')
            ?? throw new Exception("Не указан параметр в конфиге: {$config}");

        Schema::connection($connection)->dropIfExists($table);
    }


};
