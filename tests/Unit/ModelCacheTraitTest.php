<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты выбора построителя запроса модели по настройкам кеша, лога запросов и лога моделей
 * @see \Atlcom\LaravelHelper\Traits\ModelCacheTrait
 */
final class ModelCacheTraitTest extends PackageTestCase
{
    /**
     * Включённый кеш, лог запросов или лог моделей подключает построитель пакета
     * @see \Atlcom\LaravelHelper\Traits\ModelCacheTrait::newEloquentBuilder()
     *
     * @param string $enabledConfig
     * @return void
     */
    #[Test]
    #[DataProvider('helperFeatures')]
    public function newEloquentBuilderUsesHelperBuilderWhenFeatureEnabled(string $enabledConfig): void
    {
        config([
            'laravel-helper.enabled'             => true,
            'laravel-helper.query_cache.enabled' => false,
            'laravel-helper.query_log.enabled'   => false,
            'laravel-helper.model_log.enabled'   => false,
        ]);
        config(["laravel-helper.{$enabledConfig}.enabled" => true]);

        $this->assertSame(EloquentBuilder::class, (new ModelCacheTraitTestModel())->newQuery()::class);
    }


    /**
     * Без включённых функций и при выключенном пакете используется стандартный построитель Laravel
     * @see \Atlcom\LaravelHelper\Traits\ModelCacheTrait::newEloquentBuilder()
     *
     * @return void
     */
    #[Test]
    public function newEloquentBuilderUsesLaravelBuilderWhenFeaturesOrPackageDisabled(): void
    {
        config([
            'laravel-helper.enabled'             => true,
            'laravel-helper.query_cache.enabled' => false,
            'laravel-helper.query_log.enabled'   => false,
            'laravel-helper.model_log.enabled'   => false,
        ]);
        $this->assertSame(Builder::class, (new ModelCacheTraitTestModel())->newQuery()::class);

        // Глобальный выключатель пакета отключает построитель пакета даже при включённых функциях
        config([
            'laravel-helper.enabled'             => false,
            'laravel-helper.query_cache.enabled' => true,
            'laravel-helper.query_log.enabled'   => true,
            'laravel-helper.model_log.enabled'   => true,
        ]);
        $this->assertSame(Builder::class, (new ModelCacheTraitTestModel())->newQuery()::class);
    }


    /**
     * Включённый кеш запросов подключает наблюдатель кеша при загрузке модели без исключения Laravel 13
     * @see \Atlcom\LaravelHelper\Traits\ModelCacheTrait::bootModelCacheTrait()
     *
     * @return void
     */
    #[Test]
    public function bootModelCacheTraitRegistersObserverAfterModelBoot(): void
    {
        config([
            'laravel-helper.enabled'             => true,
            'laravel-helper.query_cache.enabled' => true,
        ]);

        // Модель используется только здесь, поэтому первая загрузка проходит с включённым кешем запросов
        new ModelCacheTraitObserverTestModel();

        $this->assertTrue(
            ModelCacheTraitObserverTestModel::getEventDispatcher()
                ->hasListeners('eloquent.created: ' . ModelCacheTraitObserverTestModel::class),
        );
    }


    /**
     * Возвращает разделы конфигурации, каждый из которых подключает построитель пакета
     *
     * @return array<string, array{string}>
     */
    public static function helperFeatures(): array
    {
        return [
            'query_cache' => ['query_cache'],
            'query_log'   => ['query_log'],
            'model_log'   => ['model_log'],
        ];
    }
}


/**
 * Тестовая модель для проверки выбора построителя запроса
 */
class ModelCacheTraitTestModel extends DefaultModel
{
    /** Имя таблицы не используется: проверяется только создание построителя */
    protected $table = 'model_cache_trait_checks';
}


/**
 * Тестовая модель для проверки подключения наблюдателя кеша при первой загрузке модели
 */
class ModelCacheTraitObserverTestModel extends DefaultModel
{
    /** Имя таблицы не используется: проверяется только регистрация наблюдателя */
    protected $table = 'model_cache_trait_observer_checks';
}
