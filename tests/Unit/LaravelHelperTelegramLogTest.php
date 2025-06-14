<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Tests\TestCase;
use Exception;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты отправки сообщений в телеграм
 */
final class LaravelHelperTelegramLogTest extends TestCase
{
    #[Test]
    public function exception(): void
    {
        // $this->app->singleton(ExceptionHandler::class, DefaultExceptionHandler::class);

        $this->expectException(Exception::class);
        throw new Exception('Тест');
    }
}
