<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Defaults\DefaultTest;
use Atlcom\LaravelHelper\Enums\IpBlockRuleEnum;
use Atlcom\LaravelHelper\Middlewares\IpBlockMiddleware;
use Atlcom\LaravelHelper\Services\IpBlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты middleware блокировки ip адресов
 */
final class IpBlockMiddlewareTest extends DefaultTest
{
    private string $storageFile;


    protected function setUp(): void
    {
        parent::setUp();

        $this->storageFile = storage_path('framework/testing/ip-block-middleware-test.json');

        @unlink($this->storageFile);

        Config::set('laravel-helper.ip_block.enabled', true);
        Config::set('laravel-helper.ip_block.storage_file', $this->storageFile);
        Config::set('laravel-helper.ip_block.manual_allow', []);
        Config::set('laravel-helper.ip_block.manual_deny', []);
        Config::set('laravel-helper.ip_block.ignore', []);
        Config::set('laravel-helper.ip_block.rules.requests_per_minute.enabled', false);
        Config::set('laravel-helper.ip_block.rules.not_found_per_minute.enabled', false);
        Config::set('laravel-helper.ip_block.rules.unauthorized_per_minute.enabled', false);
        Config::set('laravel-helper.ip_block.rules.suspicious_payload.enabled', false);
    }


    #[Test]
    public function returnsForbiddenForBlockedIp(): void
    {
        Config::set('laravel-helper.ip_block.manual_deny', ['203.0.113.21']);

        $middleware = app(IpBlockMiddleware::class);
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.21');

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(403, $response->getStatusCode());
    }


    #[Test]
    public function passesRequestForAllowedIp(): void
    {
        $middleware = app(IpBlockMiddleware::class);
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.22');

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }


    #[Test]
    public function passesRequestForManualAllowIpEvenIfBlocked(): void
    {
        $ip = '203.0.113.55';

        Config::set('laravel-helper.ip_block.manual_allow', [$ip]);
        Config::set('laravel-helper.ip_block.manual_deny', [$ip]);

        app(IpBlockService::class)->blockIp($ip, IpBlockRuleEnum::ManualBlock->value, 'manual');

        $middleware = app(IpBlockMiddleware::class);
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', $ip);

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }
}
