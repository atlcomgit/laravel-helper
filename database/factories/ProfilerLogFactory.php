<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Enums\ProfilerLogStatusEnum;
use Atlcom\LaravelHelper\Models\ProfilerLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика логов консольных команд
 * @extends Factory<ProfilerLog>
 */
class ProfilerLogFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<ProfilerLog>
     */
    protected $model = ProfilerLog::class;


    /**
     * Задает состояние свойств модели по умолчанию
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
