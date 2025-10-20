<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Enums\TelegramBotVariableTypeEnum;
use Atlcom\LaravelHelper\Models\TelegramBotChat;
use Atlcom\LaravelHelper\Models\TelegramBotMessage;
use Atlcom\LaravelHelper\Models\TelegramBotVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика
 * @extends Factory<TelegramBotVariable>
 */
class TelegramBotVariableFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<TelegramBotVariable>
     */
    protected $model = TelegramBotVariable::class;


    /**
     * Задает состояние свойств модели по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $telegramBotChat = TelegramBotChat::inRandomOrder()->first() ?? TelegramBotChat::factory()->create();
        $telegramBotMessage = TelegramBotMessage::inRandomOrder()->first();

        return [
            'uuid' => uuid(),
            'telegram_bot_chat_id' => $telegramBotChat?->id,
            'telegram_bot_message_id' => $telegramBotMessage?->id,
            'type' => TelegramBotVariableTypeEnum::enumRandom(),
            'name' => fake()->slug(),
            'value' => fake()->sentence(),
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
            ->afterMaking(function (TelegramBotVariable $model) {
                // Здесь ещё нет записи в БД — можно принудительно задать guarded поля
                $model->forceFill($this->definition());
            })
            ->afterCreating(function (TelegramBotVariable $model) {
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
