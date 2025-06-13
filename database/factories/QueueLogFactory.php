<?php

namespace Database\Factories;

use Atlcom\LaravelHelper\Enums\QueueLogStatusEnum;
use Atlcom\LaravelHelper\Models\QueueLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика логов очередей
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<QueueLog>
 */
class QueueLogFactory extends Factory
{
    protected $model = QueueLog::class;


    /**
     * Возвращает массив с данными для новой записи
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => uuid(),
            'job_id' => uuid(),
            'job_name' => fake()->word(),
            'name' => fake()->word(),
            'connection' => fake()->word(),
            'queue' => fake()->word(),
            'payload' => [],
            'delay' => 0,
            'attempts' => 0,
            'status' => fake()->randomElement(QueueLogStatusEnum::enumValues()),
            'exception' => null,
            'info' => null,
        ];
    }


    /**
     * Задает uuid очереди
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
     * Задает job_id очереди
     *
     * @param string $jobId
     * @return static
     */
    public function withJobId(string $jobId): static
    {
        return $this->state(fn (array $attributes): array => [
            'job_id' => $jobId,
        ]);
    }


    /**
     * Задает job_name очереди
     *
     * @param string $jobName
     * @return static
     */
    public function withJobName(string $jobName): static
    {
        return $this->state(fn (array $attributes): array => [
            'job_name' => $jobName,
        ]);
    }


    /**
     * Задает название очереди
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
     * Задает очередь очереди
     *
     * @param string $connection
     * @return static
     */
    public function withConnection(string $connection): static
    {
        return $this->state(fn (array $attributes): array => [
            'connection' => $connection,
        ]);
    }


    /**
     * Задает очередь очереди
     *
     * @param string $queue
     * @return static
     */
    public function withQueue(string $queue): static
    {
        return $this->state(fn (array $attributes): array => [
            'queue' => $queue,
        ]);
    }


    /**
     * Задает статус выполнения очереди
     *
     * @param QueueLogStatusEnum $status
     * @return static
     */
    public function withStatus(QueueLogStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }
}
