<?php

namespace Atlcom\LaravelHelper\Database\Factories;

use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;

/**
 * Фабрика логов отправки писем
 * @extends Factory<MailLog>
 */
class MailLogFactory extends Factory
{
    /**
     * Связанная с фабрикой модель
     *
     * @var class-string<MailLog>
     */
    protected $model = MailLog::class;


    /**
     * Задает состояние свойств модели по умолчанию
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'uuid'          => fake()->uuid(),
            'user_id'       => $user?->id,
            'status'        => fake()->randomElement(MailLogStatusEnum::enumValues()),
            'from'          => fake()->email(),
            'to'            => [fake()->email()],
            'cc'            => [],
            'bcc'           => [],
            'reply_to'      => [],
            'subject'       => fake()->sentence(),
            'body'          => fake()->randomHtml(),
            'attachments'   => [],
            'error_message' => null,
            'duration'      => fake()->randomFloat(4, 0, 10),
            'memory'        => fake()->numberBetween(1000, 1000000),
            'size'          => fake()->numberBetween(100, 10000),
            'info'          => [],
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
     * Задает статус отправки письма
     *
     * @param MailLogStatusEnum $status
     * @return static
     */
    public function withStatus(MailLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
