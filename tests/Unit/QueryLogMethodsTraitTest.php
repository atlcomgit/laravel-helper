<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

/**
 * Тесты условий создания лога запроса
 * @see \Atlcom\LaravelHelper\Traits\QueryLogMethodsTrait
 */
final class QueryLogMethodsTraitTest extends PackageTestCase
{
    /**
     * Выключенный сервис лога запросов не создаёт запись даже при заданном пороге длительности
     * @see \Atlcom\LaravelHelper\Traits\QueryLogMethodsTrait::createQueryLog()
     *
     * @return void
     */
    #[Test]
    public function createQueryLogSkipsDisabledServiceEvenWithDurationThreshold(): void
    {
        config([
            'laravel-helper.query_log.enabled'            => false,
            'laravel-helper.query_log.global'             => true,
            'laravel-helper.query_log.exceed_duration_ms' => 1,
        ]);

        $this->assertSame([], $this->createQueryLog());
    }


    /**
     * Включённый сервис без глобального лога и без порога длительности не создаёт запись
     * @see \Atlcom\LaravelHelper\Traits\QueryLogMethodsTrait::createQueryLog()
     *
     * @return void
     */
    #[Test]
    public function createQueryLogSkipsQueryWithoutGlobalLogAndThreshold(): void
    {
        config([
            'laravel-helper.query_log.enabled'            => true,
            'laravel-helper.query_log.global'             => false,
            'laravel-helper.query_log.exceed_duration_ms' => null,
        ]);

        $this->assertSame([], $this->createQueryLog());
    }


    /**
     * Вызывает защищённый метод создания лога у построителя запроса пакета
     *
     * @return array
     */
    private function createQueryLog(): array
    {
        $builder = DB::table('query_log_methods_checks');
        $method = new ReflectionMethod($builder, 'createQueryLog');

        return $method->invoke($builder, 'select 1');
    }
}
