<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Atlcom\LaravelHelper\Models\TelegramBotMessage>
 */
class TelegramBotMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $telegramBotChat = TelegramBotChat::inRandomOrder()->first();
        $telegramBotUser = TelegramBotUser::inRandomOrder()->first();
        $telegramBotMessage = TelegramBotMessage::inRandomOrder()->first();

        return [
            'uuid' => uuid(),
            'external_message_id' => fake()->randomNumber(),
            'external_update_id' => fake()->randomNumber(),
            'telegram_bot_chat_id' => $telegramBotChat?->id,
            'telegram_bot_user_id' => $telegramBotUser?->id,
            'telegram_bot_message_id' => $telegramBotMessage?->id,
            'text' => fake()->sentence(),
            'send_at' => fake()->dateTime(),
            'edit_at' => fake()->randomElement([null, fake()->dateTime()]),
            'info' => fake()->randomElement([null, []]),
        ];
    }


    /**
     * Задает связь с чатом телеграм бота
     *
     * @param TelegramBotChat|null $telegramBotChat
     * @return static
     */
    public function withTelegramBotChat(?TelegramBotChat $telegramBotChat): static
    {
        return $this->state(fn (array $attributes): array => [
            'telegram_bot_chat_id' => $telegramBotChat?->id,
        ]);
    }


    /**
     * Задает связь с пользователем телеграм бота
     *
     * @param TelegramBotUser|null $telegramBotUser
     * @return static
     */
    public function withTelegramBotUser(?TelegramBotChat $telegramBotUser): static
    {
        return $this->state(fn (array $attributes): array => [
            'telegram_bot_user_id' => $telegramBotUser?->id,
        ]);
    }


    /**
     * Задает связь с сообщением телеграм бота
     *
     * @param TelegramBotMessage|null $telegramBotMessage
     * @return static
     */
    public function withTelegramBotMessage(?TelegramBotMessage $telegramBotMessage): static
    {
        return $this->state(fn (array $attributes): array => [
            'telegram_bot_message_id' => $telegramBotMessage?->id,
        ]);
    }
}
