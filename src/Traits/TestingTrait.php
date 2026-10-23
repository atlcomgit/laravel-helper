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
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Трейт для подключения настройки тестов
 */
trait TestingTrait
{
    use CreatesApplication;
    // use DatabaseTransactions;


    public const ENV = 'testing';

    /**
     * Переменная окружения с PID процесса, в котором тестовая БД уже подготовлена.
     * Общая для всех классов с трейтом: static-переменные метода у каждого класса с трейтом свои,
     * а PID исключает ложный признак в дочерних процессах (fork, proc_open).
     */
    public const PREPARED_PROCESS_ENV = 'LARAVEL_HELPER_TESTING_PREPARED_PID';

    protected ?User $user = null;


    /**
     * Настройка тестового окружения пакета через Testbench
     *
     * @param mixed $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', static::ENV);
        $app['config']->set('database.default', static::ENV);
        $app['config']->set("database.connections." . static::ENV, [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);
    }


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


    /**
     * Завершение работы класса тестов
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }


    /**
     * Пропускает текущий тест, если он помечен группой ignore.
     *
     * @return void
     */
    protected function skipIgnoredGroupTestIfNeeded(): void
    {
        foreach ($this->groups() as $groupName) {
            if (strtolower($groupName) !== 'ignore') {
                continue;
            }

            // Пропускаем тест, так как группа ignore используется для отключения.
            $this->markTestSkipped('Тест помечен группой ignore');

            return;
        }
    }


    /**
     * Настройка перед запуском теста
     *
     * @return void
     */
    protected function setUp(): void
    {
        !method_exists($this, 'refreshDatabase')
            ?: throw new WithoutTelegramException("Запуск с трейтом RefreshDatabase невозможен");

        parent::setUp();

        // Имя выводится после создания приложения, чтобы учитывать настройку testing_log.test_name
        $this->printTestingTestName();
        $this->skipIgnoredGroupTestIfNeeded();

        static $started = false;
        static $user = null;

        $this->setConfig();

        if (!$started) {
            if (Lh::config(ConfigEnum::TestingLog, 'enabled')) {
                ApplicationDto::create(type: ApplicationTypeEnum::Testing, class: $this::class);

                // Регистрируем функцию завершения тестов
                // register_shutdown_function([static::class, 'onFinishTest']);

                // БД готовится один раз на процесс, даже если трейт подключён в нескольких базовых классах
                $this->prepareTestingDatabase();

                // Получаем пользователя для авторизации
                $user ??= $this->resolveTestingUser();
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
     * Выводит имя теста перед его выполнением, если это разрешено настройкой testing_log.test_name.enabled
     * @see \Atlcom\LaravelHelper\Tests\Unit\TestingTraitTest
     *
     * @return void
     */
    protected function printTestingTestName(): void
    {
        if (Lh::config(ConfigEnum::TestingLog, 'test_name.enabled', true)) {
            echo PHP_EOL . class_basename($this->toString()) . ' ';
        }
    }


    /**
     * Готовит тестовую БД миграциями и сидерами один раз на процесс для всех классов с трейтом
     * @see \Atlcom\LaravelHelper\Tests\Unit\TestingTraitTest
     *
     * Команды выполняются через Artisan::call без подмены консольного вывода, поэтому подготовка не меняет
     * окружение текущего теста. Подготовку отключает настройка testing_log.database.prepare, если проект
     * готовит БД самостоятельно. Без testing_log.database.fresh миграции запускаются только при изменении хеша.
     *
     * @return void
     */
    protected function prepareTestingDatabase(): void
    {
        if (
            !Lh::config(ConfigEnum::TestingLog, 'database.prepare', true)
            || getenv(static::PREPARED_PROCESS_ENV) === (string)getmypid()
        ) {
            return;
        }

        $hashPath = $this->getTestingMigrationsHashPath();
        $hashPrevious = File::exists($hashPath) ? File::get($hashPath) : null;

        if (
            Lh::config(ConfigEnum::TestingLog, 'database.fresh')
            || $this->getTestingMigrationsHash() !== $hashPrevious
        ) {
            // Журнал моделей отключается только на время миграций и сидеров, затем значение теста восстанавливается
            $config = ConfigEnum::ModelLog;
            $modelLogEnabled = Config::get("laravel-helper.{$config->value}.enabled");
            Config::set("laravel-helper.{$config->value}.enabled", false);

            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            try {
                $this->callTestingArtisan('migrate:fresh', ['--force' => true]);
                $this->callTestingArtisan('migrate', [
                    '--force'    => true,
                    '--path'     => __DIR__ . '/../../database/migrations',
                    '--realpath' => true,
                ]);

                // Запускаем сидеры
                if (Lh::config(ConfigEnum::TestingLog, 'database.seed')) {
                    $this->callTestingArtisan('db:seed', ['--force' => true]);
                }
            } finally {
                Config::set("laravel-helper.{$config->value}.enabled", $modelLogEnabled);
            }

            // Хеш сохраняется после миграций, чтобы следующий запуск сравнивал фактическое состояние БД
            File::ensureDirectoryExists(dirname($hashPath));
            File::put($hashPath, $this->getTestingMigrationsHash());
        }

        putenv(static::PREPARED_PROCESS_ENV . '=' . getmypid());
    }


    /**
     * Возвращает путь к файлу хеша миграций для текущего окружения и тестовой БД
     *
     * @return string
     */
    protected function getTestingMigrationsHashPath(): string
    {
        $appEnv = (string)config('app.env');
        $database = str_replace(['/', '\\'], '_', (string)DB::connection()->getDatabaseName());

        return storage_path("framework/testing/migrations_{$appEnv}_{$database}.hash");
    }


    /**
     * Возвращает хеш файлов миграций (приложения и зарегистрированных пакетами) и выполненных записей migrations
     * @see \Atlcom\LaravelHelper\Tests\Unit\TestingTraitTest
     *
     * @return string
     */
    protected function getTestingMigrationsHash(): string
    {
        $files = [];
        $paths = array_unique([database_path('migrations'), ...app('migrator')->paths()]);

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (File::files($path) as $file) {
                $files[$file->getRealPath()] = hash_file('xxh128', $file->getRealPath());
            }
        }

        ksort($files);
        $migrations = Schema::hasTable('migrations')
            ? DB::table('migrations')->orderBy('id')->pluck('migration')->all()
            : [];

        return Hlp::hashXxh128(json_encode([$files, $migrations], JSON_THROW_ON_ERROR));
    }


    /**
     * Выполняет консольную команду подготовки БД и прерывает запуск понятной ошибкой при неуспехе
     *
     * @param string $command
     * @param array $parameters
     * @return void
     * @throws WithoutTelegramException
     */
    protected function callTestingArtisan(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);

        $exitCode === 0 ?: throw new WithoutTelegramException(
            "Команда {$command} подготовки тестовой БД завершилась с кодом {$exitCode}: " . Artisan::output(),
        );
    }


    /**
     * Возвращает пользователя для авторизации тестов из настройки testing_log.user, создавая его при отсутствии
     *
     * @return Authenticatable|null
     */
    protected function resolveTestingUser(): ?Authenticatable
    {
        if (!($userData = array_filter(Lh::config(ConfigEnum::TestingLog, 'user') ?? []))) {
            return null;
        }

        try {
            $userClass = Lh::config(ConfigEnum::App, 'user');

            return method_exists($userClass, 'factory')
                ? $userClass::where($userData)->first() ?? $userClass::factory()->create($userData)
                : $userClass::firstOrCreate($userData);
        } catch (Throwable) {
            return null;
        }
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

        $config = ConfigEnum::HttpLog;
        Config::set("laravel-helper.{$config->value}.in.enabled", false);

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

        // Отключаем сервис блокировки IP
        $config = ConfigEnum::IpBlock;
        Config::set("laravel-helper.{$config->value}.enabled", false);
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
