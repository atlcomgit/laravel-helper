<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика логов консольных команд
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ProfilerLog>
 */
class ProfilerLogFactory extends Factory
{
    protected $model = ProfilerLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class' => fake()->word(),
            'method' => fake()->word(),
            'isStatic' => fake()->boolean(),
            'arguments' => [],
            'result' => null,
            'exception' => null,
            'status' => fake()->randomElement(ProfilerLogStatusEnum::enumValues()),
            'duration' => null,
            'memory' => null,
            'info' => null,
        ];
    }


    /**
     * Задает статус выполнения профилирования метода класса
     *
     * @param ProfilerLogStatusEnum $status
     * @return static
     */
    public function withStatus(ProfilerLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
