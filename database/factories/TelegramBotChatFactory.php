<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика
 * @extends Factory<TelegramBotChat>
 */
class TelegramBotChatFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<TelegramBotChat>
     */
    protected $model = TelegramBotChat::class;


    /**
     * Задает состояние свойств модели по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'external_chat_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'chat_name' => fake()->name(),
            'type' => fake()->word(),
            'info' => fake()->randomElement([null, []]),
        ];
    }


    /**
     * Конфигурация хуков фабрики
     *
     * @return static
     */
    public function configure(): static
    {
        return $this
            ->afterMaking(function (TelegramBotChat $model) {
                // Здесь ещё нет записи в БД — можно принудительно задать guarded поля
                $model->forceFill($this->definition());
            })
            ->afterCreating(function (TelegramBotChat $model) {
                // Здесь запись уже есть; можно выполнить пост-инициализацию
                // Пример: вычисление агрегатов или логирование
                // $model->refresh();
            });
    }
}
