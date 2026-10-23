<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты ленивого разбора SQL в чтениях QueryOperationsTrait
 * @see \Atlcom\LaravelHelper\Traits\QueryOperationsTrait
 */
final class QueryOperationsTraitTest extends PackageTestCase
{
    /**
     * Создает таблицу проверки чтения через конструктор запросов пакета
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('query_operations_checks', static function (Blueprint $table): void {
            $table->id();
        });
        DB::table('query_operations_checks')->insert(['id' => 1]);
    }


    /**
     * Без запроса кеша чтения не собирают SQL для кеша и не разбирают таблицы
     * @see \Atlcom\LaravelHelper\Traits\QueryOperationsTrait::isQueryCacheRequested()
     *
     * @return void
     */
    #[Test]
    public function readsSkipTableParsingWhenCacheIsNotRequested(): void
    {
        config([
            'laravel-helper.query_cache.enabled' => false,
            'laravel-helper.query_log.enabled'   => false,
        ]);
        $service = Mockery::mock(QueryCacheService::class)->makePartial();
        $service->shouldNotReceive('getTablesFromSql');
        $service->shouldNotReceive('getTablesFromModels');
        $this->app->instance(QueryCacheService::class, $service);

        $this->assertSame(1, (int)DB::selectOne('select 1 as value')->value);
        $this->assertSame([1], DB::table('query_operations_checks')->get()->pluck('id')->map('intval')->all());
    }


    /**
     * Глобально запрошенный кеш по-прежнему получает таблицы запроса для тегов кеша
     * @see \Atlcom\LaravelHelper\Traits\QueryOperationsTrait::querySelect()
     *
     * @return void
     */
    #[Test]
    public function readsParseTablesWhenCacheIsRequestedGlobally(): void
    {
        config([
            'laravel-helper.query_cache.enabled' => true,
            'laravel-helper.query_cache.global'  => true,
        ]);
        $service = Mockery::mock(QueryCacheService::class)->makePartial();
        // Пустой список таблиц оставляет чтение без кеша и проверяет только факт разбора.
        $service->shouldReceive('getTablesFromSql')->atLeast()->once()->andReturn([]);
        $service->shouldReceive('getTablesFromModels')->atLeast()->once()->andReturn([]);
        $this->app->instance(QueryCacheService::class, $service);

        $this->assertSame(1, (int)DB::selectOne('select 1 as value')->value);
        $this->assertSame([1], DB::table('query_operations_checks')->get()->pluck('id')->map('intval')->all());
    }
}
