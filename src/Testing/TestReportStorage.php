<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Testing;

/**
 * Передаёт результаты воркеров paratest главному процессу через файлы временного каталога прогона
 * @see \Atlcom\LaravelHelper\Tests\Unit\TestReportExtensionTest
 */
final class TestReportStorage
{
    // Переменная окружения, через которую воркеры paratest получают каталог прогона от главного процесса
    public const DIRECTORY_ENV = 'LARAVEL_HELPER_TESTING_REPORT_DIR';


    /**
     * Создаёт каталог прогона и передаёт его будущим воркерам через окружение
     * Symfony Process, которым paratest запускает воркеры, наследует $_ENV главного процесса
     *
     * @return string|null Каталог или null, если его не удалось создать
     */
    public static function create(): ?string
    {
        // Недоступный временный каталог отключает отчёт без предупреждения PHP в выводе тестов
        if (!is_dir($base = sys_get_temp_dir()) || !is_writable($base)) {
            return null;
        }

        $directory = $base . '/laravel-helper-test-report-' . getmypid() . '-' . bin2hex(random_bytes(4));

        if (!mkdir($directory, 0700)) {
            return null;
        }

        putenv(self::DIRECTORY_ENV . '=' . $directory);
        $_ENV[self::DIRECTORY_ENV] = $_SERVER[self::DIRECTORY_ENV] = $directory;

        return $directory;
    }


    /**
     * Возвращает каталог прогона, созданный главным процессом paratest
     *
     * @return string|null
     */
    public static function directory(): ?string
    {
        $directory = getenv(self::DIRECTORY_ENV);

        return is_string($directory) && $directory !== '' && is_dir($directory) ? $directory : null;
    }


    /**
     * Сохраняет результаты процесса отдельным файлом: перезапущенный воркер не перезаписывает прежние результаты
     *
     * @param string $directory
     * @param array{id: string, memory: int, tests: list<array>} $process
     * @return void
     */
    public static function write(string $directory, array $process): void
    {
        $file = $directory . '/' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.json';
        $json = json_encode(
            $process,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE,
        );
        file_put_contents($file, $json, LOCK_EX);
    }


    /**
     * Читает результаты всех процессов и удаляет каталог прогона вместе с окружением
     *
     * @param string $directory
     * @return list<array{id: string, memory: int, tests: list<array>}>
     */
    public static function pull(string $directory): array
    {
        $processes = [];

        foreach (glob($directory . '/*.json') ?: [] as $file) {
            $process = json_decode((string)file_get_contents($file), true);
            unlink($file);

            if (is_array($process) && isset($process['id'], $process['memory']) && is_array($process['tests'] ?? null)) {
                $process['tests'] = array_values(array_filter($process['tests'], self::isRecord(...)));
                $processes[] = $process;
            }
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }

        putenv(self::DIRECTORY_ENV);
        unset($_ENV[self::DIRECTORY_ENV], $_SERVER[self::DIRECTORY_ENV]);

        return $processes;
    }


    /**
     * Проверяет запись теста из файла воркера: повреждённая запись пропускается без предупреждений PHP в выводе
     *
     * @param mixed $test
     * @return bool
     */
    private static function isRecord(mixed $test): bool
    {
        return is_array($test)
            && is_string($test['name'] ?? null)
            && is_string($test['class'] ?? null)
            && array_key_exists('seconds', $test)
            && ($test['seconds'] === null || is_int($test['seconds']) || is_float($test['seconds']))
            && is_string($test['status'] ?? null)
            && isset(TestReport::PRIORITIES[$test['status']])
            && is_bool($test['risky'] ?? null)
            && is_int($test['assertions'] ?? null)
            && is_bool($test['counted'] ?? null);
    }
}
