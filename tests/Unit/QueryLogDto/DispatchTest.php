<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\QueryLogDto;

use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;

/**
 * Проверяет безопасную отправку query-log
 */
final class DispatchTest extends PackageTestCase
{
    /**
     * Не пробрасывает ошибку недоступного подключения очереди
     * @see \Atlcom\LaravelHelper\Dto\QueryLogDto::dispatch()
     *
     * @return void
     */
    #[Test]
    public function dispatchReturnsSameDtoWhenQueueInfrastructureThrows(): void
    {
        Config::set('laravel-helper.query_log.enabled', true);
        Config::set('laravel-helper.query_log.queue_dispatch_sync', false);
        Config::set('laravel-helper.query_log.queue_connection', 'missing-query-log-connection');
        Config::set('laravel-helper.query_log.exclude', []);

        $dto = QueryLogDto::create(
            name: 'QueryLogTest',
            query: 'select * from users',
            info: ['tables' => ['users']],
        );

        self::assertSame($dto, $dto->dispatch());
    }
}
