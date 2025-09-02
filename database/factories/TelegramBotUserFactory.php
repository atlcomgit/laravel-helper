<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика
 * @extends Factory<TelegramBotUser>
 */
class TelegramBotUserFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<TelegramBotUser>
     */
    protected $model = TelegramBotUser::class;


    /**
     * Задает состояние свойств модели по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'external_user_id' => $this->faker->unique()->numberBetween(10_000_000, 99_999_999),
            'first_name' => fake()->name(),
            'user_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'language' => $this->faker->randomElement(['ru', 'en']),
            'is_ban' => false,
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
            ->afterMaking(function (TelegramBotUser $model) {
                // Здесь ещё нет записи в БД — можно принудительно задать guarded поля
                $model->forceFill($this->definition());
            })
            ->afterCreating(function (TelegramBotUser $model) {
                // Здесь запись уже есть; можно выполнить пост-инициализацию
                // Пример: вычисление агрегатов или логирование
                // $model->refresh();
            });
    }
}
