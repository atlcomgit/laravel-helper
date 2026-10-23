<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Testing;

use Closure;
use PHPUnit\Event\Application\Finished as ApplicationFinished;
use PHPUnit\Event\Event;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Throwable;

/**
 * Расширение PHPUnit: после всех тестов печатает итоговый отчёт — время прогона, результаты, скорость,
 * распределение длительностей, медленные тесты и классы, тесты с ошибкой
 *
 * Обычный PHPUnit печатает отчёт после собственных итогов. При запуске через paratest тесты выполняют воркеры,
 * STDOUT которых не показывается: воркеры сохраняют результаты во временный каталог, а главный процесс печатает
 * отчёт при завершении. Режимы --teamcity и --no-output не дополняются отчётом.
 * Цвет отключает стандартная переменная окружения NO_COLOR.
 *
 * @see \Atlcom\LaravelHelper\Tests\Unit\TestReportExtensionTest
 */
final class TestReportExtension implements Extension, Tracer
{
    // Режимы: обычный PHPUnit, воркер paratest, главный процесс paratest
    public const SERIAL = 'serial';
    public const WORKER = 'worker';
    public const PARALLEL = 'parallel';
    // Класс главного процесса paratest: в нём тесты не выполняются и события завершения прогона не отправляются
    private const PARATEST_RUNNER = 'ParaTest\WrapperRunner\WrapperRunner';

    private TestReportCollector $collector;
    private Closure $write;
    private bool $colors;
    private float $slowSeconds = 1.0;
    private int $slowLimit = 10;


    /**
     * Задаёт вывод по умолчанию либо изолированный приёмник, цвет, режим и каталог прогона для проверок
     *
     * @param Closure|null $write Приёмник текста отчёта
     * @param bool|null $colors Выделение цветом; по умолчанию включено, если не задана непустая NO_COLOR
     * @param string|null $mode Режим работы; определяется в bootstrap()
     * @param string|null $directory Каталог прогона paratest
     */
    public function __construct(
        ?Closure $write = null,
        ?bool $colors = null,
        private ?string $mode = null,
        private ?string $directory = null,
    ) {
        $this->collector = new TestReportCollector();
        $this->write = $write ?? static fn (string $text) => fwrite(STDOUT, $text);
        $this->colors = $colors ?? ((string)getenv('NO_COLOR') === '');
    }


    /**
     * Определяет режим запуска и подключает сбор результатов
     *
     * @param Configuration $configuration
     * @param Facade $facade
     * @param ParameterCollection $parameters slowSeconds — порог медленного теста, slowLimit — длина списка
     * @return void
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if ($parameters->has('slowSeconds') && is_numeric($slow = $parameters->get('slowSeconds')) && $slow > 0) {
            $this->slowSeconds = (float)$slow;
        }

        if ($parameters->has('slowLimit') && is_numeric($limit = $parameters->get('slowLimit'))) {
            $this->slowLimit = max(0, (int)$limit);
        }

        if (getenv('PARATEST') !== false) {
            // Воркер paratest передаёт результаты, только если главный процесс создал каталог прогона
            if (($this->directory = TestReportStorage::directory()) !== null) {
                $this->mode = self::WORKER;
                $facade->registerTracer($this);
            }

            return;
        }

        // Машиночитаемый вывод для IDE и тихий режим не дополняются отчётом
        if ($configuration->noOutput() || $configuration->outputIsTeamCity()) {
            return;
        }

        if ($configuration->outputToStandardErrorStream()) {
            $this->write = static fn (string $text) => fwrite(STDERR, $text);
        }

        if (class_exists(self::PARATEST_RUNNER, false)) {
            // Главный процесс paratest печатает отчёт при завершении, после собственных итогов
            if (($this->directory = TestReportStorage::create()) !== null) {
                $this->mode = self::PARALLEL;
                register_shutdown_function($this->printParallelReport(...));
            }

            return;
        }

        $this->mode = self::SERIAL;
        $facade->registerTracer($this);
    }


    /**
     * Собирает результаты тестов; воркер сохраняет их по окончании выполнения, обычный запуск печатает отчёт
     * после итогов PHPUnit
     *
     * @param Event $event
     * @return void
     */
    public function trace(Event $event): void
    {
        if ($this->mode !== self::SERIAL && $this->mode !== self::WORKER) {
            return;
        }

        try {
            $this->collector->collect($event);

            if ($this->mode === self::WORKER && $event instanceof ExecutionFinished) {
                $this->saveWorkerReport();
            } elseif ($this->mode === self::SERIAL && $event instanceof ApplicationFinished) {
                $this->printReport([$this->process('main')]);
            }
        } catch (Throwable $exception) {
            // Сбой отчёта не влияет на тесты: после одного предупреждения сбор прекращается
            $this->mode = null;
            $this->warn($exception);
        }
    }


    /**
     * Печатает отчёт главного процесса paratest по результатам всех воркеров и удаляет каталог прогона
     *
     * @return void
     */
    public function printParallelReport(): void
    {
        if ($this->mode !== self::PARALLEL || $this->directory === null) {
            return;
        }

        try {
            $this->printReport(TestReportStorage::pull($this->directory));
        } catch (Throwable $exception) {
            $this->warn($exception);
        }
    }


    /**
     * Сохраняет результаты воркера paratest для главного процесса
     *
     * @return void
     */
    private function saveWorkerReport(): void
    {
        if ($this->directory === null) {
            return;
        }

        // Номер воркера постоянен при его перезапуске; без токенов paratest используется PID процесса
        $token = getenv('TEST_TOKEN');

        try {
            TestReportStorage::write(
                $this->directory,
                $this->process(is_string($token) && $token !== '' ? $token : (string)getmypid()),
            );
        } catch (Throwable $exception) {
            $this->warn($exception);
        }

        $this->collector = new TestReportCollector();
    }


    /**
     * Печатает отчёт по результатам процессов
     *
     * @param list<array{id: string, memory: int, tests: list<array>}> $processes
     * @return void
     */
    private function printReport(array $processes): void
    {
        try {
            // Время прогона считается от запуска процесса PHP, как его видит разработчик
            $wallSeconds = microtime(true) - (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
            $report = new TestReport($processes, $wallSeconds, $this->slowSeconds, $this->slowLimit);
            ($this->write)($report->render($this->colors));
        } catch (Throwable $exception) {
            $this->warn($exception);
        }
    }


    /**
     * Возвращает результаты текущего процесса
     *
     * @param string $id
     * @return array{id: string, memory: int, tests: list<array>}
     */
    private function process(string $id): array
    {
        return ['id' => $id, 'memory' => memory_get_peak_usage(true), 'tests' => $this->collector->tests()];
    }


    /**
     * Сообщает о сбое отчёта, не влияя на результат тестов
     *
     * @param Throwable $exception
     * @return void
     */
    private function warn(Throwable $exception): void
    {
        fwrite(STDERR, PHP_EOL . 'Итоговый отчёт тестов не сформирован: ' . $exception->getMessage() . PHP_EOL);
    }
}
