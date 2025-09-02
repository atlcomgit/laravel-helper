<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика логов роутов
 * @extends Factory<RouteLog>
 */
class RouteLogFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<RouteLog>
     */
    protected $model = RouteLog::class;


    /**
     * Задает состояние свойств модели по умолчанию
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'method' => fake()->randomElement(['get', 'post', 'put', 'patch', 'delete']),
            'url' => Str::substr(fake()->url(), 0, 2048),
            'count' => fake()->numberBetween(),
        ];
    }


    /**
     * Задает метод роута
     *
     * @param string $method
     * @return static
     */
    public function withMethod(string $method): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => $method,
        ]);
    }


    /**
     * Задает url роута
     *
     * @param string $url
     * @return static
     */
    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes): array => [
            'url' => $url,
        ]);
    }
}
