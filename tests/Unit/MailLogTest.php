<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Defaults\DefaultMailable;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Models\MailLog;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты логирования писем
 */
final class MailLogTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    protected function setUp(): void
    {
        parent::setUp();

        // Настройка базы данных
        $this->app['config']->set('database.default', 'testing');
        $this->app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Включаем логирование писем
        Config::set('laravel-helper.mail_log.enabled', true);
        Config::set('laravel-helper.mail_log.store_on_start', true);
        Config::set('laravel-helper.mail_log.table', 'helper_mail_logs');
        Config::set('laravel-helper.mail_log.connection', 'testing');

        // Миграции
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }


    /**
     * Тест успешной отправки письма
     * @see \Atlcom\LaravelHelper\Services\MailMacrosService::setMacros()
     *
     * @return void
     */
    #[Test]
    public function mailLogSuccess(): void
    {
        // Use array driver to actually "send" and fire events
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.transport', 'array');

        $mailable =

            new class extends DefaultMailable {
            public $subject = 'Test Subject';

            public function build()
            {
                $this->to('test@example.com');

                return $this->html('Test Body');
            }


            };

        Mail::sendWithLog($mailable);

        $this->assertDatabaseHas('helper_mail_logs', [
            'status'  => MailLogStatusEnum::Success,
            'subject' => 'Test Subject',
        ]);

        $log = MailLog::first();
        $this->assertNotNull($log);
        $this->assertEquals('Test Subject', $log->subject);
        $this->assertStringContainsString('test@example.com', json_encode($log->to));
    }


    /**
     * Тест ошибки отправки письма
     * @see \Atlcom\LaravelHelper\Services\MailMacrosService::setMacros()
     *
     * @return void
     */
    #[Test]
    public function mailLogFailed(): void
    {
        Config::set('laravel-helper.mail_log.store_on_start', false);

        // Mock transport to throw exception
        $mockTransport = \Mockery::mock(\Symfony\Component\Mailer\Transport\TransportInterface::class);
        $mockTransport->shouldReceive('send')->andThrow(new \Exception('Mail Error'));
        $mockTransport->shouldReceive('__toString')->andReturn('mock');

        // Register mock driver
        Config::set('mail.mailers.mock', ['transport' => 'mock']);
        Config::set('mail.default', 'mock');
        Mail::extend('mock', fn () => $mockTransport);

        $mailable =

            new class extends DefaultMailable {
            public $subject = 'Test Subject';
            public function build()
            {
                $this->to('test@example.com');
                return $this->html('Test Body');
            }


            };

        try {
            Mail::sendWithLog($mailable);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseHas('helper_mail_logs', [
            'status'        => MailLogStatusEnum::Failed,
            'error_message' => 'Mail Error',
        ]);
    }
}
