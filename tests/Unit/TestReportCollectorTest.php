<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Testing\TestReport;
use Atlcom\LaravelHelper\Testing\TestReportCollector;
use PHPUnit\Event\Code\ClassMethod;
use PHPUnit\Event\Code\TestCollection;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\ThrowableBuilder;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\AfterLastTestMethodErrored;
use PHPUnit\Event\Test\AfterLastTestMethodFinished;
use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\BeforeFirstTestMethodErrored;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Event\TestSuite\Skipped as TestSuiteSkipped;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use RuntimeException;

/**
 * Тесты сбора результатов и длительностей тестов из событий PHPUnit
 * @see \Atlcom\LaravelHelper\Testing\TestReportCollector
 */
final class TestReportCollectorTest extends TestCase
{
    /**
     * Длительность считается от начала подготовки до Finished, итог — самый важный результат
     * @see \Atlcom\LaravelHelper\Testing\TestReportCollector::collect()
     *
     * @return void
     */
    #[Test]
    public function collectMeasuresTestFromPreparationToFinished(): void
    {
        $collector = new TestReportCollector();
        $test = $this->testMethod('Tests\Feature\OrderTest', 'createsOrder');
        $collector->collect(new PreparationStarted($this->telemetry(10), $test));
        $collector->collect(new Passed($this->telemetry(11), $test));
        $collector->collect(new ConsideredRisky($this->telemetry(11), $test, 'risky'));
        // Провал в tearDown после успешного теста становится итогом теста
        $collector->collect(new Failed($this->telemetry(12), $test, ThrowableBuilder::from(new RuntimeException()), null));
        $collector->collect(new Finished($this->telemetry(12, 500000000), $test, 3));

        $this->assertSame([[
            'name'       => 'OrderTest::createsOrder',
            'class'      => 'Tests\Feature\OrderTest',
            'seconds'    => 2.5,
            'status'     => TestReport::FAILED,
            'risky'      => true,
            'assertions' => 3,
            'counted'    => true,
        ]], $collector->tests());
    }


    /**
     * Тест без Finished закрывается последним собственным событием, без хуков класса и подготовки следующего теста
     * @see \Atlcom\LaravelHelper\Testing\TestReportCollector::collect()
     *
     * @return void
     */
    #[Test]
    public function collectClosesUnfinishedTestByItsLastEvent(): void
    {
        $collector = new TestReportCollector();
        $first = $this->testMethod('Tests\Unit\FirstTest', 'a');
        $second = $this->testMethod('Tests\Unit\SecondTest', 'b');
        $collector->collect(new PreparationStarted($this->telemetry(10), $first));
        $collector->collect(new Errored($this->telemetry(13), $first, ThrowableBuilder::from(new RuntimeException())));
        $collector->collect(new AfterTestMethodFinished($this->telemetry(14), 'Tests\Unit\FirstTest'));
        $collector->collect(new AfterLastTestMethodFinished($this->telemetry(40), 'Tests\Unit\FirstTest'));
        $collector->collect(new PreparationStarted($this->telemetry(50), $second));
        $collector->collect(new Passed($this->telemetry(51), $second));

        $tests = $collector->tests();

        $this->assertSame([4.0, 1.0], array_column($tests, 'seconds'));
        $this->assertSame([TestReport::ERRORED, TestReport::PASSED], array_column($tests, 'status'));
    }


    /**
     * Тест, пропущенный до подготовки, учитывается без длительности; повторный запуск получает отдельную запись
     * @see \Atlcom\LaravelHelper\Testing\TestReportCollector::collect()
     *
     * @return void
     */
    #[Test]
    public function collectKeepsSkippedWithoutPreparationAndRepeatedRuns(): void
    {
        $collector = new TestReportCollector();
        $skipped = $this->testMethod('Tests\Unit\RequirementsTest', 'needsExtension');
        $repeated = $this->testMethod('Tests\Unit\RepeatTest', 'runs');
        $collector->collect(new Skipped($this->telemetry(1), $skipped, 'requirement'));

        foreach ([10, 20] as $start) {
            $collector->collect(new PreparationStarted($this->telemetry($start), $repeated));
            $collector->collect(new Passed($this->telemetry($start + 1), $repeated));
            $collector->collect(new Finished($this->telemetry($start + 2), $repeated, 1));
        }

        $tests = $collector->tests();

        $this->assertSame([null, 2.0, 2.0], array_column($tests, 'seconds'));
        $this->assertSame([TestReport::SKIPPED, TestReport::PASSED, TestReport::PASSED], array_column($tests, 'status'));
    }


    /**
     * Ошибки хуков класса и пропуск всего класса учитываются так же, как в итогах PHPUnit
     * @see \Atlcom\LaravelHelper\Testing\TestReportCollector::collect()
     *
     * @return void
     */
    #[Test]
    public function collectRecordsClassHookErrorsAndSkippedSuites(): void
    {
        $collector = new TestReportCollector();
        $throwable = ThrowableBuilder::from(new RuntimeException());
        $collector->collect(new BeforeFirstTestMethodErrored(
            $this->telemetry(1),
            'Tests\Unit\BrokenTest',
            new ClassMethod('Tests\Unit\BrokenTest', 'setUpBeforeClass'),
            $throwable,
        ));
        $collector->collect(new AfterLastTestMethodErrored(
            $this->telemetry(2),
            'Tests\Unit\BrokenTest',
            new ClassMethod('Tests\Unit\BrokenTest', 'tearDownAfterClass'),
            $throwable,
        ));
        $tests = TestCollection::fromArray([
            $this->testMethod('Tests\Unit\DisabledTest', 'a'),
            $this->testMethod('Tests\Unit\DisabledTest', 'b'),
        ]);
        $collector->collect(new TestSuiteSkipped(
            $this->telemetry(3),
            new TestSuiteForTestClass('Tests\Unit\DisabledTest', 2, $tests, __FILE__, 1),
            'disabled',
        ));

        $records = $collector->tests();

        $this->assertSame([
            'BrokenTest::setUpBeforeClass',
            'BrokenTest::tearDownAfterClass',
            'DisabledTest::a',
            'DisabledTest::b',
        ], array_column($records, 'name'));
        $this->assertSame([true, false, true, true], array_column($records, 'counted'));
        $this->assertSame(
            [TestReport::ERRORED, TestReport::ERRORED, TestReport::SKIPPED, TestReport::SKIPPED],
            array_column($records, 'status'),
        );
    }


    /**
     * Создаёт тестовый метод с заданным классом и именем
     *
     * @param string $class
     * @param string $method
     * @return TestMethod
     */
    private function testMethod(string $class, string $method): TestMethod
    {
        return new TestMethod(
            $class,
            $method,
            __FILE__,
            1,
            new TestDox($class, $method, $method),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
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
