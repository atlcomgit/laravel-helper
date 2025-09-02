<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Models\ModelLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

/**
 * Фабрика логов моделей
 * @extends Factory<ModelLog>
 */
class ModelLogFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<ModelLog>
     */
    protected $model = ModelLog::class;


    /**
     * Задает состояние свойств модели по умолчанию
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'user_id' => $user?->id,
            'model_type' => null,
            'model_id' => null,
            'type' => fake()->randomElement(ModelLogTypeEnum::enumValues()),
            'attributes' => [],
            'changes' => [],
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
     * Задает состояние свойства перед созданием модели
     *
     * @param Model $model
     * @return static
     */
    public function withModel(Model $model): static
    {
        return $this->state(fn (array $attributes): array => [
            'model_type' => $model::class,
            'model_id' => $model->id,
        ]);
    }
}
