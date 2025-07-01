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
use Throwable;

/**
 * Трейт для подключения настройки тестов
 */
trait TestingTrait
{
    use CreatesApplication;
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    public const ENV = 'testing';

    protected static bool $refreshDatabase = false;
    protected static ?User $user = null;


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
     * Старт тестов
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
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

        // Авторизуем все тесты
        !static::$user ?: $this->actingAs(static::$user);
    }


    /**
     * [Description for refreshDatabase]
     * @see parent::refreshDatabase()
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->baseRefreshDatabase();
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
        (($appEnv = env('APP_ENV')) === static::ENV)
            ?: throw new WithoutTelegramException("APP_ENV = {$appEnv}: не является тестовой");
        ($isTesting = (int)isTesting())
            ?: throw new WithoutTelegramException("isTesting() = {$isTesting}: не является тестовым");

        Config::set('app.debug', false);
        Config::set('app.debug_data', true);
        Config::set('app.debug_trace', true);
        Config::set('app.debug_trace_vendor', false);

        Config::set('database.default', env('DB_CONNECTION'));
        (($dbConnection = env('DB_CONNECTION')) === static::ENV)
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
        Config::set('telescope.storage.database.connection', static::ENV);

        $helperEnabled = config('laravel-helper.testing.helper.enabled');
        Config::set('laravel-helper.console_log.enabled', $helperEnabled);
        Config::set('laravel-helper.http_log.enabled', $helperEnabled);
        Config::set('laravel-helper.model_log.enabled', $helperEnabled);
        Config::set('laravel-helper.route_log.enabled', $helperEnabled);
        Config::set('laravel-helper.query_cache.enabled', $helperEnabled);
        Config::set('laravel-helper.query_log.enabled', $helperEnabled);
        Config::set('laravel-helper.queue_log.enabled', $helperEnabled);
        Config::set('laravel-helper.telegram_log.enabled', $helperEnabled);
        Config::set('laravel-helper.view_log.enabled', $helperEnabled);
        Config::set('laravel-helper.view_cache.enabled', $helperEnabled);

        $databaseTesting = config('laravel-helper.testing.database.name');
        switch (env('DB_CONNECTION_TESTING')) {
            case 'pgsql':
                DB::connection(static::ENV)->select(
                    "SELECT 1 FROM pg_database WHERE datname = ?",
                    [$databaseTesting],
                )
                    ?: DB::connection(static::ENV)->statement("CREATE DATABASE \"$databaseTesting\"");
                break;

            case 'mysql':
                DB::connection(static::ENV)->select(
                    "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?",
                    [$databaseTesting],
                )
                    ?: DB::connection(static::ENV)->statement("CREATE DATABASE `$databaseTesting`");
                break;

            case 'sqlite':
                file_exists(storage_path("{$databaseTesting}.sqlite"))
                    ?: touch(storage_path("{$databaseTesting}.sqlite"));
                break;
        }
    }


    /**
     * Настройка тестов после миграции тестовой базы
     *
     * @return void
     */
    protected function afterRefreshingDatabase()
    {
        if (!static::$refreshDatabase) {
            // Запускаем сидеры
            !config('laravel-helper.testing.database.seed') ?: $this->seed();

            $user = array_filter(config('laravel-helper.testing.user') ?? []);
            !$user ?: static::$user = User::firstOrCreate($user);
            static::$refreshDatabase = true;
        }
    }


    /**
     * Обработка исключения ошибочного теста
     *
     * @throws Throwable
     */
    protected function onNotSuccessfulTest(Throwable $t): never
    {
        parent::onNotSuccessfulTest($t);
    }
}
