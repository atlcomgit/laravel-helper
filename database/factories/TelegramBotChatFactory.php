<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Atlcom\LaravelHelper\Models\TelegramBotChat>
 */
class TelegramBotChatFactory extends Factory
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
            'external_chat_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'chat_name' => fake()->name(),
            'type' => fake()->word(),
            'info' => fake()->randomElement([null, []]),
        ];
    }
}
