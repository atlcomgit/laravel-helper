<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

/**
 * Консольная команда route:cache
 */
class RouteCacheCommand extends \Illuminate\Foundation\Console\RouteCacheCommand
{
    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        parent::handle();

        $this->call(RouteLogCleanupCommand::class, []);

        return self::SUCCESS;
    }
}
