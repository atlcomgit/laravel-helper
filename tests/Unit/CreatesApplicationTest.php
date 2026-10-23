<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Atlcom\LaravelHelper\Traits\CreatesApplication;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты создания приложения в тестах проектов на разных версиях Laravel
 * @see \Atlcom\LaravelHelper\Traits\CreatesApplication
 */
final class CreatesApplicationTest extends PackageTestCase
{
    /**
     * В Laravel 10 метод родителя абстрактный: приложение создаётся из bootstrap/app.php проекта, даже когда
     * метод вызывается у наследника класса с трейтом, как у тестов проекта, наследующих DefaultTest
     * @see \Atlcom\LaravelHelper\Traits\CreatesApplication::createApplication()
     *
     * @return void
     */
    #[Test]
    public function createApplicationBootstrapsProjectWhenParentMethodIsAbstract(): void
    {
        $workingDirectory = getcwd();
        $directory = sys_get_temp_dir() . '/laravel-helper-creates-application-' . bin2hex(random_bytes(4));
        mkdir($directory . '/bootstrap', 0700, true);
        file_put_contents($directory . '/bootstrap/app.php', <<<'PHP'
            <?php

            return new class {
                public int $bootstrapped = 0;

                public function make(string $abstract): object
                {
                    return new class ($this) {
                        public function __construct(private object $app) {}

                        public function bootstrap(): void
                        {
                            $this->app->bootstrapped++;
                        }
                    };
                }
            };
            PHP);

        try {
            chdir($directory);
            $app = (new CreatesApplicationLaravelTenTest())->createApplication();
        } finally {
            chdir($workingDirectory);
            unlink($directory . '/bootstrap/app.php');
            rmdir($directory . '/bootstrap');
            rmdir($directory);
        }

        $this->assertSame(1, $app->bootstrapped);
    }


    /**
     * В Laravel 11 и новее используется реализация родителя
     * @see \Atlcom\LaravelHelper\Traits\CreatesApplication::createApplication()
     *
     * @return void
     */
    #[Test]
    public function createApplicationUsesImplementedParentMethod(): void
    {
        $this->assertSame('parent', (new CreatesApplicationLaravelElevenTest())->createApplication()->source);
    }
}


/**
 * Базовый класс тестов Laravel 10: создание приложения не реализовано
 */
abstract class CreatesApplicationLaravelTenBase
{
    abstract public function createApplication();
}


/**
 * Базовый класс тестов Laravel 11 и новее с собственным созданием приложения
 */
abstract class CreatesApplicationLaravelElevenBase
{
    /**
     * Возвращает признак вызова реализации родителя
     *
     * @return object
     */
    public function createApplication()
    {
        return (object)['source' => 'parent'];
    }
}


/**
 * Аналог DefaultTest для Laravel 10
 */
abstract class CreatesApplicationLaravelTenDefaultTest extends CreatesApplicationLaravelTenBase
{
    use CreatesApplication;
}


/**
 * Аналог DefaultTest для Laravel 11 и новее
 */
abstract class CreatesApplicationLaravelElevenDefaultTest extends CreatesApplicationLaravelElevenBase
{
    use CreatesApplication;
}


/**
 * Тест проекта на Laravel 10, наследующий DefaultTest
 */
final class CreatesApplicationLaravelTenTest extends CreatesApplicationLaravelTenDefaultTest
{
}


/**
 * Тест проекта на Laravel 11 и новее, наследующий DefaultTest
 */
final class CreatesApplicationLaravelElevenTest extends CreatesApplicationLaravelElevenDefaultTest
{
}
