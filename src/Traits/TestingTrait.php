<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Трейт для подключения настройки тестов
 */
trait TestingTrait
{
    use CreatesApplication;
    // use DatabaseTransactions;


    public const ENV = 'testing';

    protected ?User $user = null;


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
     * Старт класса теста
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }


    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }


    /**
     * Настройка перед запуском теста
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        static $started = false;
        static $user = null;

        $this->setConfig();

        if (!$started) {
            if (Lh::config(ConfigEnum::TestingLog, 'enabled')) {
                ApplicationDto::create(type: ApplicationTypeEnum::Testing, class: $this::class);

                // Регистрируем функцию завершения тестов
                // register_shutdown_function([static::class, 'onFinishTest']);

                // Вычисляем хеш миграций
                $migrations = DB::table('migrations')->get();
                $migrationsHashCurrent = Hlp::hashXxh128(json($migrations));
                $migrationsHashPrevious = Storage::get(storage_path('framework/testing/migrations.hash'));

                // Запускаем полную миграцию БД
                if (Lh::config(ConfigEnum::TestingLog, 'database.fresh') || $migrationsHashCurrent !== $migrationsHashPrevious) {
                    $config = ConfigEnum::ModelLog;
                    Config::set("laravel-helper.{$config->value}.enabled", false);

                    $this->artisan('migrate:fresh');
                    $this->artisan('migrate', ['--path' => __DIR__ . '/../../database/migrations']);

                    // Запускаем сидеры
                    if (Lh::config(ConfigEnum::TestingLog, 'database.seed')) {
                        $this->artisan('db:seed', []);
                    }

                    Storage::put(storage_path('framework/testing/migrations.hash'), $migrationsHashCurrent);
                }

                // Получаем пользователя для авторизации
                if (!$user && $userData = array_filter(Lh::config(ConfigEnum::TestingLog, 'user') ?? [])) {
                    $userClass = Lh::config(ConfigEnum::App, 'user');
                    $user = method_exists($userClass, 'factory')
                        ? $userClass::where($user)->first() ?? $userClass::factory()->create($userData)
                        : $userClass::firstOrCreate($user);
                }
            }

            $started = true;
        }

        // Авторизуем все тесты
        !$user ?: $this->actingAs($user);

        // Открываем транзакцию
        DB::beginTransaction();
    }


    /**
     * Настройка после выполнения теста
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Откатываем транзакцию
        DB::rollBack();

        parent::tearDown();
    }


    /**
     * Настройка тестового окружения перед миграцией
     * @see ../phpunit.xml
     * 
     * @return void
     */
    protected function setConfig()
    {
        (($appEnv = env('APP_ENV')) === static::ENV)
            ?: throw new WithoutTelegramException("APP_ENV = {$appEnv}: не является тестовой");
        ($isTesting = (int)isTesting())
            ?: throw new WithoutTelegramException("isTesting() = {$isTesting}: не является тестовым");
        Config::set('app.env', $appEnv);

        Config::set('app.debug', false);
        Config::set('app.debug_data', true);
        Config::set('app.debug_trace', true);
        Config::set('app.debug_trace_vendor', false);

        $config = ConfigEnum::App;
        Config::set("laravel-helper.{$config->value}.debug", false);
        Config::set("laravel-helper.{$config->value}.debug_data", true);
        Config::set("laravel-helper.{$config->value}.debug_trace", true);
        Config::set("laravel-helper.{$config->value}.debug_trace_vendor", false);

        (($connection = env('DB_CONNECTION')) === $appEnv)
            ?: throw new WithoutTelegramException("DB_CONNECTION = {$connection}: не является тестовой");
        Config::set('database.default', $connection);

        $connectionTesting = config("database.connections.{$appEnv}");
        Config::set('telescope.storage.database.connection', $appEnv);
        Config::set('database.connections.sqlite', $connectionTesting);
        Config::set('database.connections.mysql', $connectionTesting);
        Config::set('database.connections.mariadb', $connectionTesting);
        Config::set('database.connections.pgsql', $connectionTesting);
        Config::set('database.connections.sqlsrv', $connectionTesting);

        Config::set('cache.default', env('CACHE_DRIVER'));
        Config::set('session.default', env('SESSION_DRIVER'));
        Config::set('queue.default', env('QUEUE_CONNECTION'));
        Config::set('mail.default', env('MAIL_MAILER'));
        Config::set('telescope.enabled', env('TELESCOPE_ENABLED'));

        $helperTestingEnabled = Lh::config(ConfigEnum::TestingLog, 'enabled');
        $helperLogsEnabled = $helperTestingEnabled && Lh::config(ConfigEnum::TestingLog, 'helper_logs.enabled');
        foreach ([
            ConfigEnum::ConsoleLog,
            ConfigEnum::HttpLog,
            ConfigEnum::ModelLog,
            ConfigEnum::ProfilerLog,
            ConfigEnum::RouteLog,
            ConfigEnum::QueryCache,
            ConfigEnum::QueryLog,
            ConfigEnum::QueueLog,
            ConfigEnum::TelegramLog,
            ConfigEnum::ViewCache,
            ConfigEnum::ViewLog,
        ] as $config) {
            Config::set("laravel-helper.{$config->value}.enabled", $helperLogsEnabled);
        }

        if ($helperTestingEnabled && Lh::config(ConfigEnum::TestingLog, 'database.fresh')) {
            ($databaseTesting = $connectionTesting['database'] ?: '')
                ?: throw new WithoutTelegramException(
                    "Не задано название тестовой базы данных в database.connections.{$appEnv}.database",
                );

            switch (config("database.connections.{$connection}.driver")) {
                case 'pgsql':
                    DB::connection($appEnv)->select(
                        "SELECT 1 FROM pg_database WHERE datname = ?",
                        [$databaseTesting],
                    )
                        ?: DB::connection($appEnv)->statement("CREATE DATABASE IF NOT EXISTS \"$databaseTesting\"");
                    break;

                case 'mysql':
                    DB::connection($appEnv)->select(
                        "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?",
                        [$databaseTesting],
                    )
                        ?: DB::connection($appEnv)->statement("CREATE DATABASE IF NOT EXISTS `$databaseTesting`");
                    break;

                case 'sqlite':
                    file_exists(storage_path("{$databaseTesting}.sqlite"))
                        ?: touch(storage_path("{$databaseTesting}.sqlite"));
                    break;
            }
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


    /**
     * Обрабатывает завершение выполнения всех тестов
     */
    protected static function onFinishTest(): void {}
}
