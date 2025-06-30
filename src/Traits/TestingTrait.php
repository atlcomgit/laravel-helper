<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Трейт для подключения настройки тестов
 */
trait TestingTrait
{
    use CreatesApplication;
    use RefreshDatabase;


    public const ENV = 'testing';


    /**
     * Подключает провайдеры к тестам
     *
     * @param mixed $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    /**
     * Начальная настройка тестов
     *
     * @return void
     */
    protected function setUp(): void
    {
        echo PHP_EOL . class_basename($this->toString()) . ' ';

        parent::setUp();

        // Запускаем сидеры
        $this->seed();

        // Авторизуем все тесты
        // $user = User::where('email', config('laravel-helper.testing.email'))->first();
        // $this->actingAs($user);
    }


    /**
     * Настройка тестового окружения перед миграцией
     * @see ../phpunit.xml
     * 
     * @return void
     */
    protected function beforeRefreshingDatabase()
    {
        ApplicationDto::create(type: ApplicationTypeEnum::Testing, class: $this::class);
        
        Config::set('app.env', env('APP_ENV'));
        (($appEnv = env('APP_ENV')) === self::ENV)
            ?: throw new WithoutTelegramException("APP_ENV = {$appEnv}: не является тестовой");
        ($isTesting = (int)isTesting())
            ?: throw new WithoutTelegramException("isTesting() = {$isTesting}: не является тестовым");

        Config::set('app.debug', false);
        Config::set('app.debug_data', true);
        Config::set('app.debug_trace', true);
        Config::set('app.debug_trace_vendor', false);

        Config::set('database.default', env('DB_CONNECTION'));
        (($dbConnection = env('DB_CONNECTION')) === self::ENV)
            ?: throw new WithoutTelegramException("DB_CONNECTION = {$dbConnection}: не является тестовой");

        Config::set('cache.default', env('CACHE_DRIVER'));
        Config::set('session.default', env('SESSION_DRIVER'));
        Config::set('queue.default', env('QUEUE_CONNECTION'));
        Config::set('mail.default', env('MAIL_MAILER'));
        Config::set('telescope.enabled', env('TELESCOPE_ENABLED'));

        Config::set('database.connections.sqlite', config('database.connections.testing'));
        Config::set('database.connections.mysql', config('database.connections.testing'));
        Config::set('database.connections.mariadb', config('database.connections.testing'));
        Config::set('database.connections.pgsql', config('database.connections.testing'));
        Config::set('database.connections.sqlsrv', config('database.connections.testing'));
        Config::set('telescope.storage.database.connection', self::ENV);

        Config::set('laravel-helper.console_log.enabled', false);
        Config::set('laravel-helper.http_log.enabled', false);
        Config::set('laravel-helper.model_log.enabled', false);
        Config::set('laravel-helper.route_log.enabled', false);
        Config::set('laravel-helper.query_cache.enabled', false);
        Config::set('laravel-helper.query_log.enabled', false);
        Config::set('laravel-helper.queue_log.enabled', false);
        Config::set('laravel-helper.telegram_log.enabled', false);
        Config::set('laravel-helper.view_log.enabled', false);
        Config::set('laravel-helper.view_cache.enabled', false);

        $databaseTesting = 'testing';
        switch (env('DB_CONNECTION_TESTING')) {
            case 'pgsql':
                DB::connection(self::ENV)->select("SELECT 1 FROM pg_database WHERE datname = ?", [$databaseTesting])
                    ?: DB::connection(self::ENV)->statement("CREATE DATABASE \"$databaseTesting\"");
                break;

            case 'mysql':
                DB::connection(self::ENV)->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$databaseTesting])
                    ?: DB::connection(self::ENV)->statement("CREATE DATABASE `$databaseTesting`");
                break;

            case 'sqlite':
                file_exists(storage_path("{$databaseTesting}.sqlite"))
                    ?: touch(storage_path("{$databaseTesting}.sqlite"));
                break;
        }
    }
}
