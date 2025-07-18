<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;

/**
 * Трейт для создания laravel приложения в тестах
 */
trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // $app = require __DIR__.'/../bootstrap/app.php';
        $app = require './bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::driver('bcrypt')->setRounds(4);

        return $app;
    }
}
