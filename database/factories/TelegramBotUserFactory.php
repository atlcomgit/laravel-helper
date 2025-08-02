<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Atlcom\LaravelHelper\Models\TelegramBotUser>
 */
class TelegramBotUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'external_user_id' => fake()->randomNumber(),
            'first_name' => fake()->name(),
            'user_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'language' => 'ru',
            'is_ban' => false,
            'info' => fake()->randomElement([null, []]),
        ];
    }
}
