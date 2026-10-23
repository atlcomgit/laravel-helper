<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Testing\TestReport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Тесты расчёта и форматирования итогового отчёта тестов
 * @see \Atlcom\LaravelHelper\Testing\TestReport
 */
final class TestReportTest extends TestCase
{
    /**
     * Отчёт параллельного прогона содержит время, результаты, распределение, медленные тесты, классы и ошибки
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderShowsRunStatisticsSlowTestsAndFailures(): void
    {
        $report = new TestReport([
            [
                'id'     => '1',
                'memory' => 64 * 1048576,
                'tests'  => [
                    $this->record('FastTest::a', 0.05, class: 'Tests\Unit\FastTest', assertions: 2),
                    $this->record('FastTest::b', 0.3, class: 'Tests\Unit\FastTest', risky: true),
                    $this->record('SlowTest::c', 1.5, class: 'Tests\Feature\SlowTest', assertions: 3),
                ],
            ],
            [
                'id'     => '2',
                'memory' => 128 * 1048576,
                'tests'  => [
                    $this->record('SlowTest::d', 2.25, TestReport::FAILED, 'Tests\Feature\SlowTest'),
                    $this->record('MiddleTest::e', 0.7, class: 'Tests\Feature\MiddleTest'),
                    $this->record('SkippedTest::f', null, TestReport::SKIPPED, 'Tests\Unit\SkippedTest', assertions: 0),
                ],
            ],
        ], 2.0, 1.0, 1);

        $this->assertSame(<<<'TEXT'

            ═════════════════════════════════ Итоги тестов ═════════════════════════════════
              Время прогона     2.00 s · в тестах 4.80 s · ускорение ×2.4
              Процессы          2 · в тестах от 1.85 до 2.95 s на процесс · пик памяти 128.0 MB
              Тесты             6 · успешно 4 · с ошибкой 1 · пропущено 1 · рискованных 1
              Утверждения       8
              Средняя скорость  0.960 s на тест · 3 теста в секунду
              Длительность      мин 0.050 s · медиана 0.700 s · p95 2.250 s · макс 2.250 s
              Распределение     до 0.1 s: 1 · 0.1–0.5 s: 1 · 0.5–1 s: 1 · больше 1 s: 2
              Самый быстрый     0.050 s  FastTest::a
              Самый медленный   2.250 s  SlowTest::d

              Медленные тесты, больше 1 s: 2 · 3.75 s · 78.1% времени в тестах
                2.250 s  SlowTest::d
                … и ещё 1

              Самые долгие классы:
                   3.75 s  2 теста      SlowTest
                   0.70 s  1 тест       MiddleTest
                   0.35 s  2 теста      FastTest

              Тесты с ошибкой: 1
                ✘ SlowTest::d — провал проверки
            ════════════════════════════════════════════════════════════════════════════════

            TEXT, $report->render());
    }


    /**
     * Обычный запуск показывает пик памяти процесса без ускорения и разброса загрузки
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderShowsMemoryWithoutSpeedupForSingleProcess(): void
    {
        $output = (new TestReport([
            ['id' => 'main', 'memory' => 32 * 1048576, 'tests' => [$this->record('ExampleTest::a', 0.2)]],
        ], 0.5))->render();

        $this->assertStringContainsString('  Время прогона     0.50 s · в тестах 0.20 s' . PHP_EOL, $output);
        $this->assertStringContainsString('  Пик памяти        32.0 MB' . PHP_EOL, $output);
        $this->assertStringContainsString('  Медленные тесты, больше 1 s: нет' . PHP_EOL, $output);
        $this->assertStringNotContainsString('ускорение', $output);
        $this->assertStringNotContainsString('Самые долгие классы', $output);
        $this->assertStringNotContainsString('Тесты с ошибкой', $output);
    }


    /**
     * Медленным считается тест, выводимая длительность которого больше порога
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @param float $seconds
     * @param bool $slow
     * @return void
     */
    #[Test]
    #[TestWith([1.0, false])]
    #[TestWith([1.0004, false])]
    #[TestWith([1.001, true])]
    public function renderComparesSlowThresholdByPrintedDuration(float $seconds, bool $slow): void
    {
        $output = (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [$this->record('ExampleTest::a', $seconds)]],
        ], 2.0))->render();

        $this->assertStringContainsString(
            'Медленные тесты, больше 1 s: ' . ($slow ? '1 · 1.00 s · 100.0% времени в тестах' : 'нет'),
            $output,
        );
        $this->assertStringContainsString('0.5–1 s: ' . ($slow ? 0 : 1) . ' · больше 1 s: ' . ($slow ? 1 : 0), $output);
    }


    /**
     * Порог медленного теста меняет интервалы распределения
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderBuildsDistributionByCustomThreshold(): void
    {
        $output = (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [
                $this->record('ExampleTest::a', 0.05),
                $this->record('ExampleTest::b', 0.2),
                $this->record('ExampleTest::c', 0.4),
            ]],
        ], 1.0, 0.3))->render();

        $this->assertStringContainsString('Распределение     до 0.1 s: 1 · 0.1–0.3 s: 1 · больше 0.3 s: 1', $output);
        $this->assertStringContainsString('Медленные тесты, больше 0.3 s: 1', $output);
    }


    /**
     * Цвет выделяет успех, ошибки и медленные тесты
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderHighlightsResultsWithColors(): void
    {
        $passed = (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [$this->record('ExampleTest::a', 1.5)]],
        ], 2.0))->render(true);
        $failed = (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [$this->record('ExampleTest::a', 0.2, TestReport::ERRORED)]],
        ], 2.0))->render(true);

        $this->assertStringContainsString("\033[32mуспешно 1\033[0m", $passed);
        $this->assertStringContainsString("\033[31m1.500 s\033[0m", $passed);
        $this->assertStringContainsString("\033[1m", $passed);
        $this->assertStringContainsString("\033[31mс ошибкой 1\033[0m", $failed);
        $this->assertStringContainsString("\033[31m✘\033[0m ExampleTest::a — ошибка выполнения", $failed);
        $this->assertStringNotContainsString("\033[32m", $failed);
        $this->assertStringNotContainsString("\033[", (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [$this->record('ExampleTest::a', 1.5)]],
        ], 2.0))->render());
    }


    /**
     * Ошибка setUpBeforeClass считается тестом с ошибкой, ошибка tearDownAfterClass — только ошибкой
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderCountsClassHookErrorsLikePhpunit(): void
    {
        $output = (new TestReport([
            ['id' => 'main', 'memory' => 0, 'tests' => [
                $this->record('ExampleTest::a', 0.1),
                $this->record('BrokenTest::setUpBeforeClass', null, TestReport::ERRORED),
                $this->record('ExampleTest::tearDownAfterClass', null, TestReport::ERRORED, counted: false),
            ]],
        ], 1.0))->render();

        $this->assertStringContainsString(
            'Тесты             2 · успешно 1 · с ошибкой 1 · ошибок завершения класса 1',
            $output,
        );
        $this->assertStringContainsString('Тесты с ошибкой: 2', $output);
        $this->assertStringContainsString('✘ ExampleTest::tearDownAfterClass — ошибка выполнения', $output);
    }


    /**
     * Без тестов отчёт не печатается
     * @see \Atlcom\LaravelHelper\Testing\TestReport::render()
     *
     * @return void
     */
    #[Test]
    public function renderReturnsEmptyStringWithoutTests(): void
    {
        $this->assertSame('', (new TestReport([['id' => 'main', 'memory' => 0, 'tests' => []]], 1.0))->render());
        $this->assertSame('', (new TestReport([], 1.0))->render(true));
    }


    /**
     * Возвращает запись теста в формате TestReportCollector
     *
     * @param string $name
     * @param float|null $seconds
     * @param string $status
     * @param string $class
     * @param bool $risky
     * @param int $assertions
     * @param bool $counted
     * @return array
     */
    private function record(
        string $name,
        ?float $seconds,
        string $status = TestReport::PASSED,
        string $class = 'Tests\Unit\ExampleTest',
        bool $risky = false,
        int $assertions = 1,
        bool $counted = true,
    ): array {
        return [
            'name'       => $name,
            'class'      => $class,
            'seconds'    => $seconds,
            'status'     => $status,
            'risky'      => $risky,
            'assertions' => $assertions,
            'counted'    => $counted,
        ];
    }
}
