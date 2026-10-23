<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Testing;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Test\AfterLastTestMethodErrored;
use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\BeforeFirstTestMethodErrored;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PrintedUnexpectedOutput;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\TestSuite\Skipped as TestSuiteSkipped;

/**
 * Собирает из событий PHPUnit результат, число утверждений и длительность каждого теста вместе с setUp/tearDown
 * @see \Atlcom\LaravelHelper\Tests\Unit\TestReportCollectorTest
 */
final class TestReportCollector
{
    /** @var list<array{name: string, class: string, seconds: float|null, status: string, risky: bool, assertions: int, counted: bool}> */
    private array $tests = [];

    /** @var array<string, int> Индекс последней записи теста по его идентификатору */
    private array $indexes = [];

    // Незавершённое измерение: запись теста, начало подготовки и последнее событие этого теста
    private ?int $current = null;
    private ?string $currentId = null;
    private ?HRTime $startedAt = null;
    private ?HRTime $lastAt = null;


    /**
     * Учитывает событие PHPUnit
     *
     * @param Event $event
     * @return void
     */
    public function collect(Event $event): void
    {
        if ($event instanceof PreparationStarted) {
            // PHPUnit может не отправить Finished при ошибке подготовки: прежний тест закрывается своим последним событием
            $this->close();
            $this->current = $this->add($event->test());
            $this->currentId = $event->test()->id();
            $this->startedAt = $this->lastAt = $event->telemetryInfo()->time();

            return;
        }

        if ($this->current !== null && $this->belongsToCurrent($event)) {
            $this->lastAt = $event->telemetryInfo()->time();
        }

        if ($event instanceof Finished) {
            $this->finish($event);
        } elseif ($event instanceof Passed) {
            $this->mark($event->test(), TestReport::PASSED);
        } elseif ($event instanceof Failed) {
            $this->mark($event->test(), TestReport::FAILED);
        } elseif ($event instanceof Errored) {
            $this->mark($event->test(), TestReport::ERRORED);
        } elseif ($event instanceof Skipped) {
            $this->mark($event->test(), TestReport::SKIPPED);
        } elseif ($event instanceof MarkedIncomplete) {
            $this->mark($event->test(), TestReport::INCOMPLETE);
        } elseif ($event instanceof ConsideredRisky) {
            $this->tests[$this->index($event->test())]['risky'] = true;
        } elseif ($event instanceof BeforeFirstTestMethodErrored) {
            // PHPUnit засчитывает ошибку setUpBeforeClass как выполненный тест с ошибкой
            $this->classError($event->testClassName(), $event->calledMethod()->methodName(), true);
        } elseif ($event instanceof AfterLastTestMethodErrored) {
            // Ошибка tearDownAfterClass учитывается среди ошибок, но не как отдельный тест
            $this->classError($event->testClassName(), $event->calledMethod()->methodName(), false);
        } elseif ($event instanceof TestSuiteSkipped && $event->testSuite()->isForTestClass()) {
            // Пропуск в setUpBeforeClass пропускает все тесты класса без их подготовки
            foreach ($event->testSuite()->tests() as $test) {
                $this->mark($test, TestReport::SKIPPED);
            }
        }
    }


    /**
     * Возвращает записи тестов, закрывая незавершённое измерение
     *
     * @return list<array{name: string, class: string, seconds: float|null, status: string, risky: bool, assertions: int, counted: bool}>
     */
    public function tests(): array
    {
        $this->close();

        return $this->tests;
    }


    /**
     * Проверяет, относится ли событие к измеряемому тесту, а не к общим хукам класса или следующему тесту
     *
     * @param Event $event
     * @return bool
     */
    private function belongsToCurrent(Event $event): bool
    {
        return $event instanceof AfterTestMethodFinished
            || $event instanceof PrintedUnexpectedOutput
            || (method_exists($event, 'test') && $event->test()->id() === $this->currentId);
    }


    /**
     * Завершает измерение теста по событию Finished
     *
     * @param Finished $event
     * @return void
     */
    private function finish(Finished $event): void
    {
        $index = $this->index($event->test());
        $this->tests[$index]['assertions'] = $event->numberOfAssertionsPerformed();

        if ($index === $this->current && $this->startedAt !== null) {
            $this->tests[$index]['seconds'] = $event->telemetryInfo()->time()->duration($this->startedAt)->asFloat();
            $this->reset();
        }
    }


    /**
     * Закрывает незавершённое измерение временем последнего события теста
     *
     * @return void
     */
    private function close(): void
    {
        if ($this->current !== null && $this->startedAt !== null && $this->lastAt !== null) {
            $this->tests[$this->current]['seconds'] = $this->lastAt->duration($this->startedAt)->asFloat();
        }

        $this->reset();
    }


    /**
     * Сбрасывает незавершённое измерение
     *
     * @return void
     */
    private function reset(): void
    {
        $this->current = $this->currentId = $this->startedAt = $this->lastAt = null;
    }


    /**
     * Повышает итог теста до более важного результата
     *
     * @param Test $test
     * @param string $status
     * @return void
     */
    private function mark(Test $test, string $status): void
    {
        $index = $this->index($test);

        if (TestReport::PRIORITIES[$status] > TestReport::PRIORITIES[$this->tests[$index]['status']]) {
            $this->tests[$index]['status'] = $status;
        }
    }


    /**
     * Возвращает индекс последней записи теста; тест без подготовки, например пропущенный, получает запись без замера
     *
     * @param Test $test
     * @return int
     */
    private function index(Test $test): int
    {
        return $this->indexes[$test->id()] ?? $this->add($test);
    }


    /**
     * Добавляет запись теста; повторный запуск того же теста получает отдельную запись
     *
     * @param Test $test
     * @return int
     */
    private function add(Test $test): int
    {
        $this->tests[] = [
            'name'       => $test instanceof TestMethod
                ? $this->shortName($test->className()) . '::' . $test->name()
                : $test->name(),
            'class'      => $test instanceof TestMethod ? $test->className() : $test->file(),
            'seconds'    => null,
            'status'     => TestReport::PASSED,
            'risky'      => false,
            'assertions' => 0,
            'counted'    => true,
        ];

        return $this->indexes[$test->id()] = array_key_last($this->tests);
    }


    /**
     * Добавляет ошибку хука класса
     *
     * @param string $class
     * @param string $method
     * @param bool $counted Учитывать ли ошибку как выполненный тест
     * @return void
     */
    private function classError(string $class, string $method, bool $counted): void
    {
        $this->tests[] = [
            'name'       => $this->shortName($class) . '::' . $method,
            'class'      => $class,
            'seconds'    => null,
            'status'     => TestReport::ERRORED,
            'risky'      => false,
            'assertions' => 0,
            'counted'    => $counted,
        ];
    }


    /**
     * Возвращает имя класса без пространства имён
     *
     * @param string $class
     * @return string
     */
    private function shortName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}
