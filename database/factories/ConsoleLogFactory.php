<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика логов консольных команд
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ConsoleLog>
 */
class ConsoleLogFactory extends Factory
{
    protected $model = ConsoleLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'name' => fake()->word(),
            'command' => fake()->word(),
            'cli' => fake()->sentence(),
            'output' => fake()->text(),
            'result' => 0,
            'status' => fake()->randomElement(ConsoleLogStatusEnum::enumValues()),
            'exception' => null,
            'duration' => null,
            'memory' => null,
            'info' => null,
        ];
    }


    /**
     * Задает uuid консольной команды
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
     * Задает консольную команду
     *
     * @param string $command
     * @return static
     */
    public function withCommand(string $command): static
    {
        return $this->state(fn (array $attributes): array => [
            'command' => $command,
        ]);
    }


    /**
     * Задает название консольной команды
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
     * Задает вывод консольной команды
     *
     * @param string $output
     * @return static
     */
    public function withOutput(string $output): static
    {
        return $this->state(fn (array $attributes): array => [
            'output' => $output,
        ]);
    }


    /**
     * Задает статус выполнения консольной команды
     *
     * @param ConsoleLogStatusEnum $status
     * @return static
     */
    public function withStatus(ConsoleLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
