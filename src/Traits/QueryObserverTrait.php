<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Databases\Builders\EloquentBuilder;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Observers\ModelLogObserver;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Трейт наблюдения за изменениями моделей
 *
 * Реализует логику вызова observer-методов (created, updated, deleted, truncated)
 * при выполнении CRUD-операций через конструктор запросов.
 * Поддерживает работу как с EloquentBuilder, так и с Connection (raw SQL).
 */
trait QueryObserverTrait
{
    /**
     * Запускает методы observer для логирования изменений модели
     *
     * @param ModelLogTypeEnum $type
     * @param array|string|int|null $attributes
     * @param array|null $bindings
     * @return array
     */
    public function observeModelLog(
        ModelLogTypeEnum $type,
        $attributes = null,
        $bindings = null,
    ): array {
        $result = [];

        // Обработка через EloquentBuilder
        if (
            $this instanceof EloquentBuilder
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog === null
                    && $this->getModel()->withModelLog)
                || ($this->withModelLog !== false
                    && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $observer = app(ModelLogObserver::class);

            $models = match ($type) {
                ModelLogTypeEnum::Create => [$this->getModel()],

                default => $this->getModels() ?: [$this->getModel()],
            };

            foreach ($models as $model) {
                if ($model && $model instanceof Model) {
                    if (
                        method_exists($model, 'isWithModelLog')
                        && ($model->withModelLog === true
                            || $this->withModelLog === true)
                        && method_exists($model, 'withModelLog')
                    ) {
                        (is_null($this->withModelLog)
                            || !is_null($model->withModelLog))
                            ?: $model->withModelLog = $this->withModelLog;

                        match ($type) {
                            ModelLogTypeEnum::Create
                            => $observer->created($model, $attributes),
                            ModelLogTypeEnum::Update
                            => $observer->updated($model, $attributes),
                            ModelLogTypeEnum::Delete
                            => $observer->deleted($model, $attributes),
                            ModelLogTypeEnum::SoftDelete
                            => $observer->updated($model, $attributes),
                            ModelLogTypeEnum::ForceDelete
                            => $observer->forceDeleted($model),
                            ModelLogTypeEnum::Restore
                            => $observer->restored($model),

                            default => null,
                        };

                        $model->withModelLog = false;
                    }

                    $result[] = $model->{$model->getKeyName()};
                }
            }

            // Отключаем логирование на уровне QueryBuilder,
            // чтобы избежать повторного логирования
            $this->getQuery()->setModelLog(false);
        }

        // Обработка через Connection (raw SQL)
        if (
            $this instanceof Connection
            && is_string($attributes)
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog !== false
                    && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $withModelLog = $this->withModelLog;
            $observer = app(ModelLogObserver::class);

            // Сохраняем оригинальный SQL с плейсхолдерами
            // для безопасного whereRaw
            $originalAttributes = $attributes;
            $sql = sql($attributes, $bindings ?? []);
            $table = Hlp::arrayFirst(Hlp::sqlTables($attributes));
            $modelClass = Lh::getModelClassByTable($table);

            switch ($type) {
                case ModelLogTypeEnum::Create:
                    $fields = Hlp::sqlFieldsInsert($attributes);
                    $attributes = array_combine(
                        $fields,
                        array_slice(
                            array_pad(
                                $bindings ?? [],
                                count($fields),
                                null,
                            ),
                            0,
                            count($fields),
                        ),
                    );
                    break;

                case ModelLogTypeEnum::Update:
                case ModelLogTypeEnum::SoftDelete:
                    $fields = Hlp::sqlFieldsUpdate($attributes);
                    $attributes = array_combine(
                        $fields,
                        array_slice(
                            array_pad(
                                $bindings ?? [],
                                count($fields),
                                null,
                            ),
                            0,
                            count($fields),
                        ),
                    );
                    break;

                default:
                    $fields = array_keys(
                        Hlp::arrayUnDot(
                            Hlp::arrayFlip(
                                Hlp::sqlFields($attributes, false),
                            ),
                        )[$table] ?? [],
                    );
                    $attributes = array_combine(
                        $fields,
                        array_slice(
                            array_pad(
                                $bindings ?? [],
                                count($fields),
                                null,
                            ),
                            0,
                            count($fields),
                        ),
                    );
            }

            if ($modelClass) {
                switch ($type) {
                    case ModelLogTypeEnum::Create:
                        $primaryKey = (new $modelClass())->getKeyName();
                        $model = (new $modelClass())->fill($attributes);
                        $modelId = $this->getLastInsertId();
                        $model->{$primaryKey} = $model->getKeyType() === 'int'
                            ? (int)$modelId
                            : (string)$modelId;
                        $models = [$model];
                        break;

                    default:
                        try {
                            $primaryKey = (new $modelClass())
                                ->getKeyName();

                            // Безопасный разбор WHERE из оригинального
                            // SQL с плейсхолдерами
                            $originalSql = is_string($originalAttributes)
                                ? $originalAttributes
                                : '';
                            $whereClause = Hlp::stringSplitRange(
                                $originalSql,
                                [' where ', ' WHERE '],
                                1,
                            );

                            // Вычисляем смещение биндингов
                            // для WHERE-части
                            $partsBeforeWhere = Hlp::stringSplitRange(
                                $originalSql,
                                [' where ', ' WHERE '],
                                0,
                            );
                            $bindingOffset = substr_count(
                                (string)$partsBeforeWhere,
                                '?',
                            );
                            $whereBindings = array_slice(
                                $bindings ?? [],
                                $bindingOffset,
                            );

                            $models = DB::table($table)
                                ->when(
                                    $this->withQueryLog,
                                    static fn ($q, $v)
                                    => $q->withQueryLog($v),
                                )
                                ->when(
                                    $whereClause,
                                    static fn ($q, $v)
                                    => $q->whereRaw(
                                        $v,
                                        $whereBindings,
                                    ),
                                )
                                ->get()
                                ->map(
                                    static function ($item) use ($modelClass, $primaryKey) {
                                        $model = new $modelClass(
                                            Hlp::castToArray($item),
                                        );
                                        $model->$primaryKey
                                            = $item->$primaryKey;

                                        return $model;
                                    },
                                );
                        } catch (Throwable $exception) {
                            try {
                                $whereAttributes = [];
                                foreach (
                                    $attributes as $column => $value
                                ) {
                                    $whereAttributes[$column]
                                        = match (true) {
                                            is_scalar($value) => $value,
                                            is_array($value)
                                            || is_object($value)
                                            => Hlp::castToArray(
                                                $value,
                                            ),

                                            default => $value,
                                        };
                                }

                                $models = DB::table($table)
                                    ->when(
                                        $this->withQueryLog,
                                        static fn ($q, $v)
                                        => $q->withQueryLog($v),
                                    )
                                    ->where($whereAttributes)
                                    ->get()
                                    ->map(
                                        static function ($item) use ($modelClass, $primaryKey) {
                                            $model = new $modelClass(
                                                Hlp::castToArray($item),
                                            );
                                            $model->$primaryKey
                                                = $item->$primaryKey;

                                            return $model;
                                        },
                                    );
                            } catch (Throwable $exception) {
                                $models = [];
                            }
                        }
                }

                foreach ($models as $model) {
                    if ($model && $model instanceof Model) {
                        if (
                            method_exists($model, 'isWithModelLog')
                            && method_exists($model, 'withModelLog')
                        ) {
                            (is_null($withModelLog)
                                || !is_null($model->withModelLog))
                                ?: $model->withModelLog = $withModelLog;

                            match ($type) {
                                ModelLogTypeEnum::Create
                                => $observer->created(
                                    $model,
                                    $attributes,
                                ),
                                ModelLogTypeEnum::Update
                                => $observer->updated(
                                    $model,
                                    $attributes,
                                ),
                                ModelLogTypeEnum::Delete
                                => $observer->deleted(
                                    $model,
                                    $attributes,
                                ),
                                ModelLogTypeEnum::SoftDelete
                                => $observer->updated(
                                    $model,
                                    $attributes,
                                ),
                                ModelLogTypeEnum::ForceDelete
                                => $observer->forceDeleted($model),
                                ModelLogTypeEnum::Restore
                                => $observer->restored($model),

                                default => null,
                            };

                            $model->withModelLog = false;
                        }

                        $result[] = $model->{$model->getKeyName()};
                    }
                }

                !method_exists($this, 'withModelLog')
                    ?: $this->withModelLog(false);
            }
        }

        // Обработка Truncate — логирование всех записей таблицы
        if (
            $type === ModelLogTypeEnum::Truncate
            && is_array($attributes)
            && Lh::config(ConfigEnum::ModelLog, 'enabled')
            && (
                $this->withModelLog === true
                || ($this->withModelLog !== false
                    && Lh::config(ConfigEnum::ModelLog, 'global'))
            )
        ) {
            $withModelLog = $this->withModelLog;
            $observer = app(ModelLogObserver::class);

            foreach ($attributes as $table) {
                /** @var Model $modelClass */
                $modelClass = Lh::getModelClassByTable($table);
                if (!$modelClass) {
                    continue;
                }

                /** @var \Illuminate\Database\Eloquent\Collection $models */
                $models = (
                    method_exists($modelClass, 'withTrashed')
                ? $modelClass::query()->withTrashed()
                : $modelClass::query()
                )
                    ->when(
                        $this->withQueryLog,
                        static fn ($q, $v) => $q->withQueryLog($v),
                    )
                    ->get();
                $primaryKey = with(new $modelClass)->getKeyName();
                !(($modelFirst = $models->first())
                    && $modelFirst->$primaryKey)
                    ?: $models->sortBy($modelFirst->$primaryKey);

                foreach ($models as $model) {
                    if ($model && $model instanceof Model) {
                        if (
                            method_exists($model, 'isWithModelLog')
                            && method_exists($model, 'withModelLog')
                        ) {
                            (is_null($withModelLog)
                                || !is_null($model->withModelLog))
                                ?: $model->withModelLog = $withModelLog;

                            $observer->truncated($model);

                            $model->withModelLog = false;
                        }

                        $result[] = $model->{$model->getKeyName()};
                    }
                }
            }
        }

        return $result;
    }
}
