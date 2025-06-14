<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\ArrayableTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Абстрактный класс для очередей
 */
abstract class DefaultJob implements ShouldQueue, Arrayable
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ArrayableTrait;


    /** Флаг включения логирования очереди */
    public bool $withJobLog = false;


    /**
     * Устанавливает флаг логирования очереди
     *
     * @param bool|null $enabled
     * @return static
     */
    public function withJobLog(?bool $enabled = null): static
    {
        $this->withJobLog = $enabled ?? true;

        return $this;
    }
}
