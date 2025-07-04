<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;

/**
 * Фабрика логов рендеринга blade шаблонов
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ViewLog>
 */
class ViewLogFactory extends Factory
{
    protected $model = ViewLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'uuid' => uuid(),
            'user_id' => $user?->id,
            'name' => fake()->name(),
            'data' => [],
            'merge_data' => null,
            'render' => fake()->text(),
            'cache_key' => fake()->md5(),
            'is_cached' => fake()->boolean(),
            'is_from_cache' => fake()->boolean(),
            'status' => fake()->randomElement(ViewLogStatusEnum::enumValues()),
            'duration' => null,
            'memory' => null,
            'info' => null,
        ];
    }


    /**
     * Задает uuid очереди
     *
     * @param string $uuid
     * @return static
     */
    public function withUuid(string $uuid): static
    {
        return $this->state(fn (array $attributes): array => [
            'uuid' => $uuid,
        ]);
    }


    /**
     * Задает состояние свойства перед созданием модели
     *
     * @param User $user
     * @return static
     */
    public function withUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }


    /**
     * Задает название blade шаблона
     *
     * @param string $name
     * @return static
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
        ]);
    }


    /**
     * Задает статус выполнения рендеринга
     *
     * @param ViewLogStatusEnum $status
     * @return static
     */
    public function withStatus(ViewLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
