<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * @internal
 * Сервис регистрации классов singleton
 */
class SingletonService
{
    /**
     * Кеширует классы singleton
     *
     * @return array
     */
    public static function optimize(): array
    {
        $result = [];

        if (!Lh::config(ConfigEnum::App, 'singleton.enabled')) {
            return $result;
        }

        $singletonClasses = Lh::config(ConfigEnum::App, 'singleton.classes', []);
        $appPath = app_path();
        $singletonFiles = [];

        foreach (File::allFiles($appPath) as $file) {
            $class = Lh::getClassFromFile($file);

            if (!$class || !class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            foreach ($singletonClasses as $singletonClass) {
                if ($singletonClass && $reflection->isSubclassOf($singletonClass)) {
                    $singletonFiles[] = $class;
                    $result[] = "✔ singleton {$class}";
                }
            }
        }

        $singletonPath = storage_path('framework/singleton.php');
        File::put($singletonPath, '<?php return ' . var_export($singletonFiles, true) . ';');

        return $result;
    }


    /**
     * Регистрирует классы singleton
     *
     * @return void
     */
    public static function register(): void
    {
        if (!Lh::config(ConfigEnum::App, 'singleton.enabled')) {
            return;
        }

        $singletonPath = storage_path('framework/singleton.php');

        if (!$singletonPath || !file_exists($singletonPath)) {
            return;
        }

        $singletonFiles = require $singletonPath;

        foreach ($singletonFiles ?? [] as $singletonFile) {
            !($singletonFile && class_exists($singletonFile)) ?: app()->singleton($singletonFile);
        }
    }
}
