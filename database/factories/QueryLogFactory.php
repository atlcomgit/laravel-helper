<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\QueryLogStatusEnum;
use Atlcom\LaravelHelper\Models\ConsoleLog;
use Atlcom\LaravelHelper\Models\HttpLog;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Models\QueryLog;
use Atlcom\LaravelHelper\Models\QueueLog;
use Atlcom\LaravelHelper\Models\RouteLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика логов query запросов
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<QueryLog>
 */
class QueryLogFactory extends Factory
{
    protected $model = QueryLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'model_type' => fake()->randomElement([
                ConsoleLog::class,
                HttpLog::class,
                ModelLog::class,
                QueryLog::class,
                QueueLog::class,
                RouteLog::class,
            ]),
            'model_id' => fake()->randomNumber(),
            'query' => fake()->text(),
            'cache_key' => fake()->md5(),
            'is_cached' => fake()->boolean(),
            'is_from_cache' => fake()->boolean(),
            'status' => fake()->randomElement(QueryLogStatusEnum::enumValues()),
            'info' => null,
        ];
    }


    /**
     * Задает статус выполнения задачи
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
