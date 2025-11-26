<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Сервис миграции
 */
class MigrationService extends DefaultService
{
    /**
     * Добавляет колонку user_id с типом, соответствующим User модели.
     *
     * @param Blueprint $table Таблица миграции
     * @return void
     */
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


    /**
     * Отключает QueryCache во время выполнения миграций.
     *
     * @return void
     */
    public function disableQueryCacheDuringMigrations(): void
    {
        if (!app()->runningInConsole()) {
            return;
        }

        $command = $this->getConsoleCommand();

        if (!$command || !$this->isMigrationCommand($command)) {
            return;
        }

        config(['laravel-helper.' . ConfigEnum::QueryCache->value . '.enabled' => false]);
    }


    /**
     * Возвращает текущую artisan-команду.
     *
     * @return string|null
     */
    protected function getConsoleCommand(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        return $argv[1] ?? null;
    }


    /**
     * Проверяет, относится ли команда к миграциям.
     *
     * @param string $command Имя команды
     * @return bool
     */
    protected function isMigrationCommand(string $command): bool
    {
        $commands = [
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'migrate:refresh',
            'migrate:reset',
            'migrate:rollback',
            'migrate:status',
            'db:wipe',
        ];

        return in_array($command, $commands, true)
            || Str::startsWith($command, 'migrate:');
    }
}
