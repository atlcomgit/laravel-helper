<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика
 * @extends Factory<TelegramBotMessage>
 */
class TelegramBotMessageFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<TelegramBotMessage>
     */
    protected $model = TelegramBotMessage::class;


    /**
     * Задает состояние свойств модели по умолчанию
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
     * Конфигурация хуков фабрики
     *
     * @return static
     */
    public function configure(): static
    {
        return $this
            ->afterMaking(function (TelegramBotMessage $model) {
                // Здесь ещё нет записи в БД — можно принудительно задать guarded поля
                $model->forceFill($this->definition());
            })
            ->afterCreating(function (TelegramBotMessage $model) {
                // Здесь запись уже есть; можно выполнить пост-инициализацию
                // Пример: вычисление агрегатов или логирование
                // $model->refresh();
            });
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
