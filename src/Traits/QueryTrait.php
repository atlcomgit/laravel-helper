<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Databases\Builders\QueryBuilder;
use Illuminate\Database\Connection;

/**
 * Трейт для подключений кеширования к конструктору query запросов
 *
 * Композиция подтрейтов:
 * - QueryFlagsTrait: управление флагами (withQueryCache, withQueryLog, withModelLog)
 * - QueryLogMethodsTrait: создание/обновление/ошибка записей лога запросов
 * - QueryOperationsTrait: CRUD-операции (get, select, insert, create, update, delete, truncate, clearCache)
 * - QueryObserverTrait: наблюдение за изменениями моделей (observeModelLog)
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TValue
 *
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryCache(int|bool|null $seconds = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutCache(int|bool|null $seconds = null)
 *
 * @method self|EloquentBuilder|QueryBuilder|TModel withQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutQueryLog(?bool $enabled = null)
 * @method self|EloquentBuilder|QueryBuilder|TModel withoutLog(?bool $enabled = null)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Connection
 */
trait QueryTrait
{
    use QueryFlagsTrait;
    use QueryLogMethodsTrait;
    use QueryOperationsTrait;
    use QueryObserverTrait;
}
