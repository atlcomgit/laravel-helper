<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\QueryLogJob;

use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Jobs\QueryLogJob;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Проверяет безопасное выполнение задачи query-log
 */
final class InvokeTest extends PackageTestCase
{
    /**
     * Не пробрасывает ошибку query-log repository
     * @see \Atlcom\LaravelHelper\Jobs\QueryLogJob::__invoke()
     *
     * @return void
     */
    #[Test]
    public function invokeDoesNotThrowWhenRepositoryInfrastructureFails(): void
    {
        Config::set('laravel-helper.query_log.model', stdClass::class);

        $dto = QueryLogDto::create(
            name: 'QueryLogJobTest',
            query: 'select * from users',
            info: ['tables' => ['users']],
        );

        (new QueryLogJob($dto))();

        self::assertTrue(true);
    }
}
