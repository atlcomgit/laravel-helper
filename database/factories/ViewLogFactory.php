<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\ViewLogStatusEnum;
use Atlcom\LaravelHelper\Models\ViewLog;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            'uuid' => uuid(),
            'name' => fake()->name(),
            'data' => [],
            'merge_data' => null,
            'render' => fake()->text(),
            'cache_key' => fake()->md5(),
            'is_cached' => fake()->boolean(),
            'is_from_cache' => fake()->boolean(),
            'status' => fake()->randomElement(ViewLogStatusEnum::enumValues()),
            'info' => null,
        ];
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
     * Задает статус выполнения задачи
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
