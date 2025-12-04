<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Relations;

use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Кастомное отношение для получения предыдущего сообщения с заполненным slug
 */
class TelegramBotMessagePreviousMessageWithSlugRelation extends Relation
{
    public function __construct(Builder $query, TelegramBotMessage $parent)
    {
        parent::__construct($query, $parent);
    }


    /**
     * Базовые ограничения для отношения отсутствуют
     *
     * @return void
     */
    public function addConstraints()
    {
        // Нет ограничений, т.к. выборка строится вручную
    }


    /**
     * Не добавляем ограничения для жадной загрузки
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // Жадная загрузка обрабатывается вручную через match
    }


    /**
     * Инициализирует отношение на наборе моделей
     *
     * @param array $models
     * @param string $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }


    /**
     * Сопоставляет результаты отношения с моделями
     *
     * @param array $models
     * @param EloquentCollection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->resolvePreviousMessage($model));
        }

        return $models;
    }


    /**
     * Возвращает результат отношения
     *
     * @return TelegramBotMessage|null
     */
    public function getResults()
    {
        return $this->resolvePreviousMessage($this->parent);
    }


    /**
     * Разрешает предыдущее сообщение со slug для переданной модели
     *
     * @param TelegramBotMessage $model
     * @return TelegramBotMessage|null
     */
    protected function resolvePreviousMessage(TelegramBotMessage $model): ?TelegramBotMessage
    {
        $message = $this->resolvePreviousRelation($model);
        $visited = [];

        while ($message !== null) {
            if (!empty($message->slug)) {
                return $message;
            }

            $messageId = $message->getKey();

            if ($messageId !== null) {
                if (isset($visited[$messageId])) {
                    break;
                }

                $visited[$messageId] = true;
            }

            $message = $this->resolvePreviousRelation($message);
        }

        return null;
    }


    /**
     * Возвращает непосредственное предыдущее сообщение модели
     *
     * @param TelegramBotMessage $model
     * @return TelegramBotMessage|null
     */
    protected function resolvePreviousRelation(TelegramBotMessage $model): ?TelegramBotMessage
    {
        if ($model->relationLoaded('previousMessage')) {
            /** @var TelegramBotMessage|null $previous */
            $previous = $model->getRelation('previousMessage');

            return $previous;
        }

        return $model->previousMessage()->getResults();
    }
}
