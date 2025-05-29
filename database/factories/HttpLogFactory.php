<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Enums\HttpLogMethodEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Models\HttpLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;

/**
 * Фабрика логов http запросов
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<HttpLog>
 */
class HttpLogFactory extends Factory
{
    protected $model = HttpLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'uuid' => fake()->uuid(),
            'user_id' => $user?->id,
            'name' => fake()->randomElement(HttpLogHeaderEnum::enumValues()),
            'type' => fake()->randomElement(HttpLogTypeEnum::enumValues()),
            'method' => fake()->randomElement(HttpLogMethodEnum::enumValues()),
            'status' => fake()->randomElement(HttpLogStatusEnum::enumValues()),
            'url' => Str::substr(fake()->url(), 0, 2048),
            'request_headers' => [],
            'request_data' => json_encode(['test' => true]),
            'request_hash' => fake()->md5(),
            'response_code' => fake()->randomElement([200, 300, 400, 500]),
            'response_message' => fake()->text(190),
            'response_headers' => [],
            'response_data' => json_encode(['test' => true]),
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
}
