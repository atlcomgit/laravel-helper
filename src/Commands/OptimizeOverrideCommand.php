<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Illuminate\Foundation\Console\OptimizeCommand;

/**
 * Консольная команда optimize
 */
class OptimizeOverrideCommand extends OptimizeCommand
{
    protected $signature = 'optimize';
    protected $description = 'Оптимизация laravel с вызовом laravel-helper optimize';


    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        parent::handle();

        $this->call('lh:optimize');

        return self::SUCCESS;
    }
}
