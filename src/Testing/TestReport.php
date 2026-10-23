<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Testing;

use Atlcom\Hlp;

/**
 * Итоговый отчёт прогона тестов: статистика результатов и длительностей в виде текста для терминала
 * @see \Atlcom\LaravelHelper\Testing\TestReportExtension
 * @see \Atlcom\LaravelHelper\Tests\Unit\TestReportTest
 */
final class TestReport
{
    // Результаты теста; при нескольких событиях итогом становится результат с большим приоритетом
    public const PASSED = 'passed';
    public const SKIPPED = 'skipped';
    public const INCOMPLETE = 'incomplete';
    public const FAILED = 'failed';
    public const ERRORED = 'errored';
    public const PRIORITIES = [
        self::PASSED     => 0,
        self::SKIPPED    => 1,
        self::INCOMPLETE => 2,
        self::FAILED     => 3,
        self::ERRORED    => 4,
    ];
    // Число строк в списках самых долгих классов и тестов с ошибкой
    private const CLASS_LIMIT = 5;
    private const FAILURE_LIMIT = 20;
    // Ширина разделителя и колонки названий строк отчёта
    private const WIDTH = 80;
    private const LABEL_WIDTH = 18;
    // Отступы строк отчёта и элементов его списков
    private const INDENT = '  ';
    private const ITEM_INDENT = '    ';
    // Границы интервалов распределения длительностей, меньшие порога медленного теста, секунды
    private const DISTRIBUTION_BOUNDS = [0.1, 0.5];
    // ANSI-последовательности оформления
    private const BOLD = "\033[1m";
    private const RED = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const RESET = "\033[0m";

    /**
     * Записи тестов всех процессов; counted = false у ошибки tearDownAfterClass, которая не является тестом
     *
     * @var list<array{name: string, class: string, seconds: float|null, status: string, risky: bool, assertions: int, counted: bool}>
     */
    private array $tests = [];

    /** @var array<string, array{seconds: float, memory: int}> Время в тестах и пик памяти каждого процесса */
    private array $processes = [];

    // Выделение цветом для текущего вызова render()
    private bool $colors = false;


    /**
     * @param list<array{id: string, memory: int, tests: list<array>}> $processes Результаты процессов: один при
     *     обычном запуске PHPUnit, по одному на каждый запуск воркера paratest
     * @param float $wallSeconds Время прогона по часам
     * @param float $slowSeconds Длительность, больше которой тест считается медленным
     * @param int $slowLimit Число медленных тестов в списке
     */
    public function __construct(
        array $processes,
        private readonly float $wallSeconds,
        private readonly float $slowSeconds = 1.0,
        private readonly int $slowLimit = 10,
    ) {
        foreach ($processes as $process) {
            // Перезапущенный воркер paratest сохраняет свой номер: результаты его запусков объединяются
            $id = (string)$process['id'];
            $this->processes[$id] ??= ['seconds' => 0.0, 'memory' => 0];
            $this->processes[$id]['memory'] = max($this->processes[$id]['memory'], (int)$process['memory']);

            foreach ($process['tests'] as $test) {
                $this->tests[] = $test;
                $this->processes[$id]['seconds'] += (float)$test['seconds'];
            }
        }
    }


    /**
     * Возвращает отчёт для терминала или пустую строку, если тесты не выполнялись
     *
     * @param bool $colors Выделять цветом результаты и медленные тесты
     * @return string
     */
    public function render(bool $colors = false): string
    {
        $this->colors = $colors;
        $tests = array_values(array_filter($this->tests, static fn (array $test): bool => $test['counted']));
        $failures = array_values(array_filter(
            $this->tests,
            static fn (array $test): bool => in_array($test['status'], [self::FAILED, self::ERRORED], true),
        ));

        if ($tests === [] && $failures === []) {
            return '';
        }

        // Длительность есть только у тестов, дошедших до подготовки: пропуск по требованиям не измеряется
        $measured = array_values(array_filter($tests, static fn (array $test): bool => $test['seconds'] !== null));
        usort($measured, static fn (array $left, array $right): int => $left['seconds'] <=> $right['seconds']);
        $total = (float)array_sum(array_column($measured, 'seconds'));

        $lines = [
            '',
            $this->title('Итоги тестов'),
            $this->row('Время прогона', $this->runTime($total)),
            $this->processesRow(),
            $this->row('Тесты', $this->results($tests, $failures)),
            $this->row('Утверждения', (string)array_sum(array_column($tests, 'assertions'))),
            ...$this->durations($measured, $total, count($tests)),
            ...$this->slowTests($measured, $total),
            ...$this->slowClasses($measured),
            ...$this->failures($failures),
            $this->paint(str_repeat('═', self::WIDTH), self::BOLD),
        ];

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }


    /**
     * Возвращает время прогона, суммарное время в тестах и ускорение параллельного запуска
     *
     * @param float $total
     * @return string
     */
    private function runTime(float $total): string
    {
        $parts = [sprintf('%.2f s', $this->wallSeconds), sprintf('в тестах %.2f s', $total)];

        // Ускорение — отношение суммарного времени тестов ко времени прогона, имеет смысл только для воркеров
        if (count($this->processes) > 1 && $this->wallSeconds > 0) {
            $parts[] = sprintf('ускорение ×%.1f', $total / $this->wallSeconds);
        }

        return implode(' · ', $parts);
    }


    /**
     * Возвращает строку процессов paratest с разбросом их загрузки или пик памяти обычного запуска
     *
     * @return string
     */
    private function processesRow(): string
    {
        $memory = sprintf('%.1f MB', max([0, ...array_column($this->processes, 'memory')]) / 1048576);

        if (count($this->processes) < 2) {
            return $this->row('Пик памяти', $memory);
        }

        // Разброс времени в тестах показывает неравномерность распределения тестов по воркерам
        $busy = array_column($this->processes, 'seconds');

        return $this->row('Процессы', implode(' · ', [
            (string)count($this->processes),
            sprintf('в тестах от %.2f до %.2f s на процесс', min($busy), max($busy)),
            "пик памяти {$memory}",
        ]));
    }


    /**
     * Возвращает число тестов по результатам
     *
     * @param list<array> $tests
     * @param list<array> $failures
     * @return string
     */
    private function results(array $tests, array $failures): string
    {
        $statuses = array_count_values(array_column($tests, 'status'));
        $failed = ($statuses[self::FAILED] ?? 0) + ($statuses[self::ERRORED] ?? 0);
        $parts = [
            (string)count($tests),
            $this->paint('успешно ' . ($statuses[self::PASSED] ?? 0), $failures === [] ? self::GREEN : ''),
            $this->paint("с ошибкой {$failed}", $failed > 0 ? self::RED : ''),
        ];

        // Ошибка tearDownAfterClass не относится к отдельному тесту, но PHPUnit учитывает её среди ошибок
        if (($classErrors = count($failures) - $failed) > 0) {
            $parts[] = $this->paint("ошибок завершения класса {$classErrors}", self::RED);
        }

        foreach ([self::SKIPPED => 'пропущено', self::INCOMPLETE => 'не завершено'] as $status => $label) {
            if (($statuses[$status] ?? 0) > 0) {
                $parts[] = $this->paint("{$label} {$statuses[$status]}", self::YELLOW);
            }
        }

        if (($risky = count(array_filter($tests, static fn (array $test): bool => $test['risky']))) > 0) {
            $parts[] = $this->paint("рискованных {$risky}", self::YELLOW);
        }

        return implode(' · ', $parts);
    }


    /**
     * Возвращает строки средней скорости, крайних значений и распределения длительностей
     *
     * @param list<array> $measured Измеренные тесты по возрастанию длительности
     * @param float $total
     * @param int $count
     * @return list<string>
     */
    private function durations(array $measured, float $total, int $count): array
    {
        if ($measured === []) {
            return [];
        }

        $values = array_column($measured, 'seconds');
        $fastest = $measured[0];
        $slowest = $measured[count($measured) - 1];
        $speed = $this->wallSeconds > 0 ? $count / $this->wallSeconds : 0.0;
        $speedText = $speed >= 10 ? (string)(int)round($speed) : $this->number(round($speed, 1));

        return [
            $this->row('Средняя скорость', $this->duration($total / count($values)) . ' на тест · '
                . Hlp::stringPlural($speedText, ['тестов', 'тест', 'теста']) . ' в секунду'),
            $this->row('Длительность', implode(' · ', [
                'мин ' . $this->duration($values[0]),
                'медиана ' . $this->duration($this->median($values)),
                'p95 ' . $this->duration($this->percentile($values, 95)),
                'макс ' . $this->duration($values[count($values) - 1]),
            ])),
            $this->row('Распределение', $this->distribution($values)),
            $this->row('Самый быстрый', $this->duration($fastest['seconds']) . '  ' . $fastest['name']),
            $this->row('Самый медленный', $this->duration($slowest['seconds']) . '  ' . $slowest['name']),
        ];
    }


    /**
     * Возвращает число тестов в интервалах длительности; последний интервал — медленные тесты
     *
     * @param list<float> $values
     * @return string
     */
    private function distribution(array $values): string
    {
        // Верхние границы интервалов: фиксированные границы меньше порога и сам порог включительно
        $edges = [
            ...array_filter(self::DISTRIBUTION_BOUNDS, fn (float $bound): bool => $bound < $this->slowSeconds),
            $this->slowSeconds,
        ];
        $counts = array_fill(0, count($edges) + 1, 0);

        foreach ($values as $seconds) {
            $counts[$this->interval(round($seconds, 3), $edges)]++;
        }

        $parts = [];
        $lower = null;

        foreach ($edges as $index => $edge) {
            $label = $lower === null ? 'до ' . $this->number($edge) : $this->number($lower) . '–' . $this->number($edge);
            $parts[] = "{$label} s: {$counts[$index]}";
            $lower = $edge;
        }

        $slowCount = $counts[count($edges)];
        $parts[] = $this->paint(
            'больше ' . $this->number($this->slowSeconds) . " s: {$slowCount}",
            $slowCount > 0 ? self::RED : '',
        );

        return implode(' · ', $parts);
    }


    /**
     * Возвращает номер интервала длительности
     *
     * @param float $seconds
     * @param list<float> $edges
     * @return int
     */
    private function interval(float $seconds, array $edges): int
    {
        $last = count($edges) - 1;

        foreach ($edges as $index => $edge) {
            if ($seconds < $edge || ($index === $last && $seconds <= $edge)) {
                return $index;
            }
        }

        return $last + 1;
    }


    /**
     * Возвращает список медленных тестов по убыванию длительности
     *
     * @param list<array> $measured
     * @param float $total
     * @return list<string>
     */
    private function slowTests(array $measured, float $total): array
    {
        $slow = array_reverse(array_values(array_filter(
            $measured,
            fn (array $test): bool => $this->isSlow($test['seconds']),
        )));
        $title = 'Медленные тесты, больше ' . $this->number($this->slowSeconds) . ' s';

        if ($slow === []) {
            return ['', self::INDENT . "{$title}: нет"];
        }

        $seconds = (float)array_sum(array_column($slow, 'seconds'));
        $lines = ['', self::INDENT . sprintf(
            '%s: %s · %.2f s · %.1f%% времени в тестах',
            $title,
            $this->paint((string)count($slow), self::RED),
            $seconds,
            $total > 0 ? $seconds / $total * 100 : 0.0,
        )];

        foreach (array_slice($slow, 0, $this->slowLimit) as $test) {
            $lines[] = self::ITEM_INDENT . $this->duration($test['seconds']) . '  ' . $test['name'];
        }

        if (count($slow) > $this->slowLimit) {
            $lines[] = self::ITEM_INDENT . '… и ещё ' . (count($slow) - $this->slowLimit);
        }

        return $lines;
    }


    /**
     * Возвращает классы с наибольшим суммарным временем тестов
     *
     * @param list<array> $measured
     * @return list<string>
     */
    private function slowClasses(array $measured): array
    {
        $classes = [];

        foreach ($measured as $test) {
            $classes[$test['class']] ??= ['seconds' => 0.0, 'tests' => 0];
            $classes[$test['class']]['seconds'] += $test['seconds'];
            $classes[$test['class']]['tests']++;
        }

        // Для одного класса список повторил бы строку времени прогона
        if (count($classes) < 2) {
            return [];
        }

        uasort($classes, static fn (array $left, array $right): int => $right['seconds'] <=> $left['seconds']);
        $lines = ['', self::INDENT . 'Самые долгие классы:'];

        foreach (array_slice($classes, 0, self::CLASS_LIMIT, true) as $class => $data) {
            $tests = $this->pad(Hlp::stringPlural($data['tests'], ['тестов', 'тест', 'теста']), 11);
            $seconds = sprintf('%.2f s', $data['seconds']);
            $lines[] = self::ITEM_INDENT . sprintf('%9s  %s  %s', $seconds, $tests, $this->shortName((string)$class));
        }

        return $lines;
    }


    /**
     * Возвращает список тестов с ошибкой
     *
     * @param list<array> $failures
     * @return list<string>
     */
    private function failures(array $failures): array
    {
        if ($failures === []) {
            return [];
        }

        $lines = ['', self::INDENT . $this->paint('Тесты с ошибкой: ' . count($failures), self::RED)];

        foreach (array_slice($failures, 0, self::FAILURE_LIMIT) as $test) {
            $kind = $test['status'] === self::FAILED ? 'провал проверки' : 'ошибка выполнения';
            $lines[] = self::ITEM_INDENT . $this->paint('✘', self::RED) . " {$test['name']} — {$kind}";
        }

        if (count($failures) > self::FAILURE_LIMIT) {
            $lines[] = self::ITEM_INDENT . '… и ещё ' . (count($failures) - self::FAILURE_LIMIT);
        }

        return $lines;
    }


    /**
     * Возвращает заголовок отчёта по центру разделителя
     *
     * @param string $title
     * @return string
     */
    private function title(string $title): string
    {
        $title = " {$title} ";
        $left = intdiv(self::WIDTH - mb_strlen($title), 2);
        $right = self::WIDTH - mb_strlen($title) - $left;

        return $this->paint(str_repeat('═', $left) . $title . str_repeat('═', $right), self::BOLD);
    }


    /**
     * Возвращает строку отчёта с выровненным названием
     *
     * @param string $label
     * @param string $value
     * @return string
     */
    private function row(string $label, string $value): string
    {
        return self::INDENT . $this->pad($label, self::LABEL_WIDTH) . $value;
    }


    /**
     * Дополняет текст пробелами до ширины в символах, а не в байтах
     *
     * @param string $text
     * @param int $width
     * @return string
     */
    private function pad(string $text, int $width): string
    {
        return $text . str_repeat(' ', max(0, $width - mb_strlen($text)));
    }


    /**
     * Возвращает длительность теста, выделяя медленную красным
     *
     * @param float $seconds
     * @return string
     */
    private function duration(float $seconds): string
    {
        return $this->paint(sprintf('%.3f s', $seconds), $this->isSlow($seconds) ? self::RED : '');
    }


    /**
     * Проверяет медленный тест по выводимому значению: 1.0004 s печатается как 1.000 и медленным не считается
     *
     * @param float $seconds
     * @return bool
     */
    private function isSlow(float $seconds): bool
    {
        return round($seconds, 3) > $this->slowSeconds;
    }


    /**
     * Возвращает медиану отсортированных значений
     *
     * @param list<float> $values
     * @return float
     */
    private function median(array $values): float
    {
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }


    /**
     * Возвращает процентиль отсортированных значений методом ближайшего ранга:
     * длительность, которую не превышает заданная доля тестов
     *
     * @param list<float> $values
     * @param int $percent
     * @return float
     */
    private function percentile(array $values, int $percent): float
    {
        return $values[max(0, (int)ceil($percent / 100 * count($values)) - 1)];
    }


    /**
     * Возвращает число без незначащих нулей
     *
     * @param float $value
     * @return string
     */
    private function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3f', $value), '0'), '.');
    }


    /**
     * Возвращает имя класса без пространства имён или имя файла теста
     *
     * @param string $class
     * @return string
     */
    private function shortName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }


    /**
     * Выделяет текст ANSI-оформлением, если цвет включён
     *
     * @param string $text
     * @param string $style
     * @return string
     */
    private function paint(string $text, string $style): string
    {
        return $this->colors && $style !== '' ? $style . $text . self::RESET : $text;
    }
}
