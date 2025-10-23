<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сервис миграции
 */
class MigrationService extends DefaultService
{
    public function addForeignUser(Blueprint $table): void
    {
        $userTableName = Lh::config(ConfigEnum::HttpLog, 'user.table_name');
        $userPrimaryKeyName = Lh::config(ConfigEnum::HttpLog, 'user.primary_key');
        $userPrimaryKeyType = Lh::config(ConfigEnum::HttpLog, 'user.primary_type');

        if ($userTableName && $userPrimaryKeyName && $userPrimaryKeyType) {
            // Обработка типа user_id в зависимости от типа ключа
            switch ($userPrimaryKeyType) {
                case 'string':
                    $table->string('user_id')->nullable(true)->index()->comment('Id пользователя');
                    break;
                case 'uuid':
                    $table->uuid('user_id')->nullable(true)->index()->comment('Id пользователя');
                    break;
                case 'integer':
                case 'bigint':
                default:
                    $connectionName = Schema::getConnection()->getName();
                    switch ($connectionName) {
                        case 'pgsql':
                            $table->bigInteger('user_id')->nullable(true)->index()->comment('Id пользователя');
                            break;
                        case 'sqlite':
                            $table->unsignedBigInteger('user_id')->nullable(true)->index()->comment('Id пользователя');
                            break;
                        default: // mysql и остальные
                            $table->unsignedBigInteger('user_id')->nullable(true)->index()->comment('Id пользователя');
                            break;
                    }
                    break;
            }

            // $table->foreign('user_id')
            //     ->references($userPrimaryKeyName)->on($userTableName)->onUpdate('cascade')->onDelete('restrict');
        }

    }
}
