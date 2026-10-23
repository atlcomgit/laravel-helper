<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;
use ReflectionMethod;

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
        // parent:: в трейте относится к родителю класса, подключившего трейт, а не к классу конкретного теста:
        // в Laravel 10 метод родителя абстрактный, а у наследника DefaultTest он уже реализован этим трейтом
        $parentClass = get_parent_class(self::class);
        $canUseParentCreateApplication = $parentClass
            && method_exists($parentClass, 'createApplication')
            && !(new ReflectionMethod($parentClass, 'createApplication'))->isAbstract();

        if ($canUseParentCreateApplication) {
            $app = parent::createApplication();
        } else {
            $app = require './bootstrap/app.php';
            $app->make(Kernel::class)->bootstrap();
        }

        Hash::driver('bcrypt')->setRounds(4);

        return $app;
    }
}
