<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;

/**
 * Фабрика логов query запросов
 * @extends Factory<QueryLog>
 */
class QueryLogFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<QueryLog>
     */
    protected $model = QueryLog::class;


    /**
     * Задает состояние свойств модели по умолчанию
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'uuid' => uuid(),
            'user_id' => $user?->id,
            'name' => fake()->randomElement([
                ConsoleLog::class,
                HttpLog::class,
                ModelLog::class,
                QueryLog::class,
                QueueLog::class,
                RouteLog::class,
            ]),
            'query' => fake()->text(),
            'cache_key' => fake()->md5(),
            'is_cached' => fake()->boolean(),
            'is_from_cache' => fake()->boolean(),
            'status' => fake()->randomElement(QueryLogStatusEnum::enumValues()),
            'duration' => null,
            'memory' => null,
            'count' => null,
            'info' => null,
        ];
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
     * Задает статус выполнения очереди
     *
     * @param QueryLogStatusEnum $status
     * @return static
     */
    public function withStatus(QueryLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
