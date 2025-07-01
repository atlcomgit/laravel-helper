<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Defaults\DefaultTest;
use Exception;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты отправки сообщений в телеграм
 */
final class LaravelHelperTelegramLogTest extends DefaultTest
{
    #[Test]
    public function exception(): void
    {
        // $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);

        $this->expectException(Exception::class);
        throw new Exception('Тест');
    }
}
