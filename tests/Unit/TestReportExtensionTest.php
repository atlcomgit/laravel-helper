<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Testing\TestReportExtension;
use Atlcom\LaravelHelper\Testing\TestReportStorage;
use PHPUnit\Event\Application\Finished as ApplicationFinished;
use PHPUnit\Event\Code\TestMethodBuilder;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Тесты печати итогового отчёта при обычном запуске PHPUnit и при запуске через paratest
 * @see \Atlcom\LaravelHelper\Testing\TestReportExtension
 * @see \Atlcom\LaravelHelper\Testing\TestReportStorage
 */
final class TestReportExtensionTest extends TestCase
{
    /**
     * Обычный запуск печатает отчёт после итогов PHPUnit, а не по окончании выполнения тестов
     * @see \Atlcom\LaravelHelper\Testing\TestReportExtension::trace()
     *
     * @return void
     */
    #[Test]
    public function serialModePrintsReportAfterApplicationFinished(): void
    {
        $output = '';
        $extension = new TestReportExtension(static function (string $text) use (&$output): void {
            $output .= $text;
        }, false, TestReportExtension::SERIAL);
        $this->traceTest($extension, 1.25);
        $extension->trace(new ExecutionFinished($this->telemetry(20)));

        $this->assertSame('', $output);

        $extension->trace(new ApplicationFinished($this->telemetry(21), 0));

        $this->assertStringContainsString(' Итоги тестов ', $output);
        $this->assertStringContainsString('  Тесты             1 · успешно 1 · с ошибкой 0' . PHP_EOL, $output);
        $this->assertStringContainsString('  Утверждения       2' . PHP_EOL, $output);
        $this->assertStringContainsString(
            '  Самый медленный   1.250 s  TestReportExtensionTest::' . $this->name() . PHP_EOL,
            $output,
        );
        $this->assertMatchesRegularExpression('/  Пик памяти        \d+\.\d MB/u', $output);
    }


    /**
     * Воркеры paratest сохраняют результаты в каталог прогона, главный процесс печатает общий отчёт
     * и удаляет каталог вместе с переменной окружения
     * @see \Atlcom\LaravelHelper\Testing\TestReportExtension::printParallelReport()
     *
     * @return void
     */
    #[Test]
    public function parallelModePrintsReportFromWorkerResults(): void
    {
        $token = getenv('TEST_TOKEN');
        $directory = TestReportStorage::create();
        $this->assertNotNull($directory);

        try {
            $this->assertSame($directory, getenv(TestReportStorage::DIRECTORY_ENV));
            $this->assertSame($directory, $_ENV[TestReportStorage::DIRECTORY_ENV]);
            $this->assertSame($directory, TestReportStorage::directory());

            foreach (['1' => 2.0, '2' => 3.0] as $workerToken => $seconds) {
                putenv("TEST_TOKEN={$workerToken}");
                $worker = new TestReportExtension(
                    static fn (string $text) => null,
                    false,
                    TestReportExtension::WORKER,
                    $directory,
                );
                $this->traceTest($worker, $seconds);
                $worker->trace(new ExecutionFinished($this->telemetry(20)));
            }

            $this->assertCount(2, glob($directory . '/*.json'));

            $output = '';
            (new TestReportExtension(static function (string $text) use (&$output): void {
                $output .= $text;
            }, false, TestReportExtension::PARALLEL, $directory))->printParallelReport();

            $this->assertStringContainsString('  Процессы          2 · в тестах от 2.00 до 3.00 s на процесс', $output);
            $this->assertStringContainsString('  Тесты             2 · успешно 2 · с ошибкой 0' . PHP_EOL, $output);
            $this->assertStringContainsString('  Утверждения       4' . PHP_EOL, $output);
            $this->assertDirectoryDoesNotExist($directory);
            $this->assertFalse(getenv(TestReportStorage::DIRECTORY_ENV));
            $this->assertArrayNotHasKey(TestReportStorage::DIRECTORY_ENV, $_ENV);
            $this->assertArrayNotHasKey(TestReportStorage::DIRECTORY_ENV, $_SERVER);
        } finally {
            putenv($token === false ? 'TEST_TOKEN' : "TEST_TOKEN={$token}");
            $this->removeDirectory($directory);
        }
    }


    /**
     * Повреждённые файлы и записи воркеров пропускаются, остальные результаты сохраняются
     * @see \Atlcom\LaravelHelper\Testing\TestReportStorage::pull()
     *
     * @return void
     */
    #[Test]
    public function pullSkipsMalformedWorkerResults(): void
    {
        $directory = TestReportStorage::create();
        $this->assertNotNull($directory);

        try {
            $valid = [
                'name'       => 'ExampleTest::a',
                'class'      => 'Tests\Unit\ExampleTest',
                'seconds'    => 0.5,
                'status'     => 'passed',
                'risky'      => false,
                'assertions' => 1,
                'counted'    => true,
            ];
            TestReportStorage::write($directory, [
                'id'     => '1',
                'memory' => 1,
                'tests'  => [$valid, ['name' => 'BrokenTest::a'], [...$valid, 'status' => 'unknown'], 'text'],
            ]);
            file_put_contents($directory . '/broken.json', '{');

            $this->assertSame([['id' => '1', 'memory' => 1, 'tests' => [$valid]]], TestReportStorage::pull($directory));
            $this->assertDirectoryDoesNotExist($directory);
        } finally {
            $this->removeDirectory($directory);
        }
    }


    /**
     * Цвет по умолчанию включён и отключается непустой переменной окружения NO_COLOR
     * @see \Atlcom\LaravelHelper\Testing\TestReportExtension::__construct()
     *
     * @param string $noColor
     * @param bool $colors
     * @return void
     */
    #[Test]
    #[TestWith(['1', false])]
    #[TestWith(['', true])]
    public function noColorEnvironmentDisablesColorsByDefault(string $noColor, bool $colors): void
    {
        $previous = getenv('NO_COLOR');
        $output = '';

        try {
            putenv("NO_COLOR={$noColor}");
            $extension = new TestReportExtension(static function (string $text) use (&$output): void {
                $output .= $text;
            }, mode: TestReportExtension::SERIAL);
        } finally {
            putenv($previous === false ? 'NO_COLOR' : "NO_COLOR={$previous}");
        }

        $this->traceTest($extension, 1.5);
        $extension->trace(new ApplicationFinished($this->telemetry(20), 0));

        $this->assertSame($colors, str_contains($output, "\033[31m1.500 s\033[0m"));
        $this->assertSame($colors, str_contains($output, "\033["));
    }


    /**
     * Передаёт расширению события успешного теста с заданной длительностью и двумя утверждениями
     *
     * @param TestReportExtension $extension
     * @param float $seconds
     * @return void
     */
    private function traceTest(TestReportExtension $extension, float $seconds): void
    {
        $test = TestMethodBuilder::fromTestCase($this);
        $extension->trace(new PreparationStarted($this->telemetry(10), $test));
        $extension->trace(new Passed($this->telemetry(11), $test));
        $extension->trace(new Finished(
            $this->telemetry(10 + (int)$seconds, (int)round(($seconds - (int)$seconds) * 1e9)),
            $test,
            2,
        ));
    }


    /**
     * Удаляет каталог прогона и переменную окружения после неуспешной проверки, чтобы не влиять на следующие тесты
     *
     * @param string $directory
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        foreach (glob($directory . '/*.json') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }

        putenv(TestReportStorage::DIRECTORY_ENV);
        unset($_ENV[TestReportStorage::DIRECTORY_ENV], $_SERVER[TestReportStorage::DIRECTORY_ENV]);
    }


    /**
     * Создаёт телеметрию с заданным монотонным временем
     *
     * @param int $seconds
     * @param int $nanoseconds
     * @return Info
     */
    private function telemetry(int $seconds, int $nanoseconds = 0): Info
    {
        $memory = MemoryUsage::fromBytes(0);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $snapshot = new Snapshot(
            HRTime::fromSecondsAndNanoseconds($seconds, $nanoseconds),
            $memory,
            $memory,
            new GarbageCollectorStatus(0, 0, 0, 0, null, null, null, null, null, null, null, null),
        );

        return new Info($snapshot, $duration, $memory, $duration, $memory);
    }
}
