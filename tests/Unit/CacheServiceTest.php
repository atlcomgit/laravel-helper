<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Services\CacheService;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты сервиса кеширования
 */
final class CacheServiceTest extends PackageTestCase
{
    private const DATABASE_GZ_BASE64_PREFIX = 'lh-gzbase64:v1:';


    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'testing');
        Config::set('database.connections.testing', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);

        Config::set('cache.default', 'database');
        Config::set('cache.prefix', '');
        Config::set('cache.stores.database', [
            'driver'     => 'database',
            'connection' => 'testing',
            'table'      => 'cache',
        ]);

        Config::set('laravel-helper.query_cache.driver', 'database');
        Config::set('laravel-helper.query_cache.enabled', true);
        Config::set('laravel-helper.query_cache.gzdeflate.enabled', true);
        Config::set('laravel-helper.query_cache.gzdeflate.level', 9);

        app('cache')->forgetDriver(['database', 'array']);

        Schema::dropIfExists('cache');
        Schema::create('cache', static function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }


    #[Test]
    public function databaseStoreStoresGzipAsMarkedTextSafePayload(): void
    {
        $key = 'cache-service-database-gzip-marker';
        $value = [
            'items' => [
                ['id' => 1, 'name' => 'first'],
                ['id' => 2, 'name' => 'second'],
            ],
            'meta'  => [
                'total' => 2,
                'page'  => 1,
            ],
        ];

        $result = app(CacheService::class)->setCache(ConfigEnum::QueryCache, [], $key, $value, 3600);

        $this->assertTrue($result);

        $payload = Cache::driver('database')->get($key);
        $this->assertIsString($payload);
        $this->assertStringStartsWith(self::DATABASE_GZ_BASE64_PREFIX, $payload);

        $rawValue = DB::table('cache')->where('key', $key)->value('value');
        $this->assertIsString($rawValue);
        $this->assertTrue(mb_check_encoding($rawValue, 'UTF-8'));

        $this->assertSame($value, app(CacheService::class)->getCache(ConfigEnum::QueryCache, [], $key));
    }


    #[Test]
    public function databaseStoreReadsLegacyRawGzipPayload(): void
    {
        $key = 'cache-service-database-legacy-gzip';
        $value = [
            'legacy' => true,
            'items'  => range(1, 5),
        ];

        Cache::driver('database')->put($key, gzdeflate(serialize($value), 9), 3600);

        $payload = Cache::driver('database')->get($key);
        $this->assertIsString($payload);
        $this->assertStringStartsNotWith(self::DATABASE_GZ_BASE64_PREFIX, $payload);

        $this->assertSame($value, app(CacheService::class)->getCache(ConfigEnum::QueryCache, [], $key));
    }


    #[Test]
    public function corruptMarkedDatabasePayloadIsCacheMiss(): void
    {
        $key = 'cache-service-database-corrupt-marker';

        Cache::driver('database')->put($key, self::DATABASE_GZ_BASE64_PREFIX . 'not-valid-base64', 3600);

        $this->assertNull(app(CacheService::class)->getCache(ConfigEnum::QueryCache, [], $key));
    }


    #[Test]
    public function arrayStoreGzipRoundTripIsUnchanged(): void
    {
        Config::set('laravel-helper.query_cache.driver', 'array');
        app('cache')->forgetDriver('array');

        $key = 'cache-service-array-gzip';
        $value = [
            'driver' => 'array',
            'items'  => [
                ['id' => 1],
                ['id' => 2],
            ],
        ];

        $result = app(CacheService::class)->setCache(ConfigEnum::QueryCache, [], $key, $value, 3600);

        $this->assertTrue($result);
        $this->assertSame($value, app(CacheService::class)->getCache(ConfigEnum::QueryCache, [], $key));
    }


    #[Test]
    public function databaseStoreClearCacheDeletesRowsByAnyTaggedKeySegment(): void
    {
        $matchingKey = '__QueryCacheService__users__posts__hash_matching';
        $unrelatedKey = '__QueryCacheService__orders__hash_unrelated';

        Cache::driver('database')->put($matchingKey, 'matching', 3600);
        Cache::driver('database')->put($unrelatedKey, 'unrelated', 3600);

        app(CacheService::class)->clearCache(ConfigEnum::QueryCache, ['QueryCacheService', 'posts']);

        $this->assertFalse(DB::table('cache')->where('key', $matchingKey)->exists());
        $this->assertTrue(DB::table('cache')->where('key', $unrelatedKey)->exists());
    }


    #[Test]
    public function databaseStoreClearCacheAllDeletesOnlyQueryCacheRows(): void
    {
        $queryCacheKey = '__QueryCacheService__users__hash_full';
        $otherCacheKey = 'plain-cache-key';

        Cache::driver('database')->put($queryCacheKey, 'query-cache', 3600);
        Cache::driver('database')->put($otherCacheKey, 'other-cache', 3600);

        app(CacheService::class)->clearCache(ConfigEnum::QueryCache, [ConfigEnum::QueryCache->value]);

        $this->assertFalse(DB::table('cache')->where('key', $queryCacheKey)->exists());
        $this->assertTrue(DB::table('cache')->where('key', $otherCacheKey)->exists());
    }


    #[Test]
    public function databaseStoreClearCacheDoesNothingWhenCacheTableDoesNotExist(): void
    {
        Schema::dropIfExists('cache');

        app(CacheService::class)->clearCache(ConfigEnum::QueryCache, ['users']);

        $this->assertFalse(Schema::hasTable('cache'));
    }
}
