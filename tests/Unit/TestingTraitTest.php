<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты служебных методов TestingTrait: вывод имени теста и однократная подготовка тестовой БД
 * @see \Atlcom\LaravelHelper\Traits\TestingTrait
 */
final class TestingTraitTest extends PackageTestCase
{
    /** Временный файл хеша миграций, изолированный от storage тестового приложения. */
    private string $hashPath;

    /** @var list<array{0: string, 1: array}> Консольные команды подготовки, записанные вместо выполнения. */
    private array $artisanCalls = [];

    /** Режим команд подготовки: null — выполнять по-настоящему, false — записывать, true — записывать и падать. */
    private ?bool $artisanFails = false;


    /**
     * Сбрасывает признак подготовленной БД, файл хеша и записанные команды перед каждой проверкой
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->hashPath = sys_get_temp_dir() . '/laravel-helper-testing-trait-' . getmypid() . '.hash';
        @unlink($this->hashPath);
        putenv(self::PREPARED_PROCESS_ENV);
    }


    /**
     * Удаляет признак подготовленной БД и файл хеша, чтобы не влиять на другие тесты процесса
     *
     * @return void
     */
    protected function tearDown(): void
    {
        putenv(self::PREPARED_PROCESS_ENV);
        @unlink($this->hashPath);

        parent::tearDown();
    }


    /**
     * Направляет хеш миграций во временный файл вместо storage тестового приложения
     *
     * @return string
     */
    protected function getTestingMigrationsHashPath(): string
    {
        return $this->hashPath;
    }


    /**
     * Записывает команды подготовки вместо выполнения либо имитирует их ошибку в зависимости от режима теста
     *
     * @param string $command
     * @param array $parameters
     * @return void
     * @throws WithoutTelegramException
     */
    protected function callTestingArtisan(string $command, array $parameters = []): void
    {
        if ($this->artisanFails === null) {
            parent::callTestingArtisan($command, $parameters);

            return;
        }

        $this->artisanCalls[] = [$command, $parameters];

        !$this->artisanFails ?: throw new WithoutTelegramException("Команда {$command} завершилась ошибкой");
    }


    /**
     * Имя теста выводится только при включённой настройке testing_log.test_name.enabled
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::printTestingTestName()
     *
     * @return void
     */
    #[Test]
    public function printTestingTestNameRespectsConfig(): void
    {
        config(['laravel-helper.testing_log.test_name.enabled' => false]);
        ob_start();
        $this->printTestingTestName();
        $disabledOutput = ob_get_clean();

        config(['laravel-helper.testing_log.test_name.enabled' => true]);
        ob_start();
        $this->printTestingTestName();
        $enabledOutput = ob_get_clean();

        $this->assertSame('', $disabledOutput);
        $this->assertStringContainsString('TestingTraitTest::printTestingTestNameRespectsConfig', $enabledOutput);
    }


    /**
     * Отключённая подготовка не запускает консольные команды и не отмечает процесс подготовленным
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::prepareTestingDatabase()
     *
     * @return void
     */
    #[Test]
    public function prepareTestingDatabaseSkipsWhenDisabled(): void
    {
        config(['laravel-helper.testing_log.database.prepare' => false]);

        $this->prepareTestingDatabase();

        $this->assertSame([], $this->artisanCalls);
        $this->assertFalse(getenv(self::PREPARED_PROCESS_ENV));
    }


    /**
     * Миграции и сидеры выполняются один раз на процесс, повторный вызов ничего не запускает
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::prepareTestingDatabase()
     *
     * @return void
     */
    #[Test]
    public function prepareTestingDatabaseRunsCommandsOncePerProcess(): void
    {
        config([
            'laravel-helper.testing_log.database.prepare' => true,
            'laravel-helper.testing_log.database.fresh'   => true,
            'laravel-helper.testing_log.database.seed'    => true,
        ]);

        $this->prepareTestingDatabase();
        $this->prepareTestingDatabase();

        $this->assertSame(
            ['migrate:fresh', 'migrate', 'db:seed'],
            array_column($this->artisanCalls, 0),
        );
        $this->assertTrue($this->artisanCalls[1][1]['--realpath']);
        $this->assertSame((string)getmypid(), getenv(self::PREPARED_PROCESS_ENV));
        $this->assertSame($this->getTestingMigrationsHash(), file_get_contents($this->hashPath));
    }


    /**
     * Без режима fresh неизменный хеш миграций не запускает команды, но отмечает процесс подготовленным
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::getTestingMigrationsHash()
     *
     * @return void
     */
    #[Test]
    public function prepareTestingDatabaseSkipsUnchangedMigrationsWithoutFresh(): void
    {
        config([
            'laravel-helper.testing_log.database.prepare' => true,
            'laravel-helper.testing_log.database.fresh'   => false,
        ]);
        file_put_contents($this->hashPath, $this->getTestingMigrationsHash());

        $this->prepareTestingDatabase();

        $this->assertSame([], $this->artisanCalls);
        $this->assertSame((string)getmypid(), getenv(self::PREPARED_PROCESS_ENV));
    }


    /**
     * Без режима fresh изменённый хеш миграций запускает пересоздание БД без сидеров при их отключении
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::prepareTestingDatabase()
     *
     * @return void
     */
    #[Test]
    public function prepareTestingDatabaseMigratesWhenHashChanged(): void
    {
        config([
            'laravel-helper.testing_log.database.prepare' => true,
            'laravel-helper.testing_log.database.fresh'   => false,
            'laravel-helper.testing_log.database.seed'    => false,
        ]);
        file_put_contents($this->hashPath, 'устаревший хеш');

        $this->prepareTestingDatabase();

        $this->assertSame(['migrate:fresh', 'migrate'], array_column($this->artisanCalls, 0));
        $this->assertSame($this->getTestingMigrationsHash(), file_get_contents($this->hashPath));
    }


    /**
     * Ошибка команды подготовки прерывает запуск и не отмечает процесс подготовленным
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::prepareTestingDatabase()
     *
     * @return void
     */
    #[Test]
    public function prepareTestingDatabaseKeepsProcessUnpreparedAfterFailure(): void
    {
        config([
            'laravel-helper.testing_log.database.prepare' => true,
            'laravel-helper.testing_log.database.fresh'   => true,
        ]);
        $this->artisanFails = true;

        try {
            $this->prepareTestingDatabase();
            $this->fail('Ожидалось исключение подготовки тестовой БД');
        } catch (WithoutTelegramException $exception) {
            $this->assertStringContainsString('migrate:fresh', $exception->getMessage());
        }

        $this->assertFalse(getenv(self::PREPARED_PROCESS_ENV));
        $this->assertFileDoesNotExist($this->hashPath);
    }


    /**
     * Неуспешная консольная команда превращается в исключение с её выводом
     * @see \Atlcom\LaravelHelper\Traits\TestingTrait::callTestingArtisan()
     *
     * @return void
     */
    #[Test]
    public function callTestingArtisanReportsExitCodeAndOutput(): void
    {
        $this->artisanFails = null;

        try {
            // Без таблицы migrations команда штатно завершается кодом 1
            $this->callTestingArtisan('migrate:status');
            $this->fail('Ожидалось исключение неуспешной команды');
        } catch (WithoutTelegramException $exception) {
            $this->assertStringContainsString('migrate:status', $exception->getMessage());
            $this->assertStringContainsString('кодом 1', $exception->getMessage());
            $this->assertStringContainsString('Migration table not found', $exception->getMessage());
        }
    }
}
