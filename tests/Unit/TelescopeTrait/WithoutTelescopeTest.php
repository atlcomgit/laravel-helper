<?php

declare(strict_types=1);

namespace Laravel\Telescope {
    /**
     * Тестовый переключатель состояния Telescope
     */
    final class Telescope
    {
        /** Текущее состояние записи Telescope */
        public static bool $recording = true;


        /**
         * Возвращает состояние записи
         *
         * @return bool
         */
        public static function isRecording(): bool
        {
            return self::$recording;
        }


        /**
         * Включает запись
         *
         * @return void
         */
        public static function startRecording(): void
        {
            self::$recording = true;
        }


        /**
         * Выключает запись
         *
         * @return void
         */
        public static function stopRecording(): void
        {
            self::$recording = false;
        }
    }
}

namespace Atlcom\LaravelHelper\Tests\Unit\TelescopeTrait {
    use Atlcom\LaravelHelper\Tests\PackageTestCase;
    use Atlcom\LaravelHelper\Traits\TelescopeTrait;
    use Laravel\Telescope\Telescope;
    use PHPUnit\Framework\Attributes\Test;
    use RuntimeException;

    /**
     * Проверяет восстановление состояния Telescope
     */
    final class WithoutTelescopeTest extends PackageTestCase
    {
        /**
         * Восстанавливает запись после исключения callback
         * @see \Atlcom\LaravelHelper\Traits\TelescopeTrait::withoutTelescope()
         *
         * @return void
         */
        #[Test]
        public function withoutTelescopeRestoresRecordingAfterCallbackFailure(): void
        {
            Telescope::$recording = true;
            $exception = new RuntimeException('Исходная ошибка callback');
            $service = new class {
                use TelescopeTrait;
            };

            try {
                $service->withoutTelescope(static fn () => throw $exception);
                self::fail('Ожидалось исходное исключение callback');
            } catch (RuntimeException $actualException) {
                self::assertSame($exception, $actualException);
            }

            self::assertTrue(Telescope::isRecording());
        }
    }
}
