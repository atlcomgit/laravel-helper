<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Atlcom\LaravelHelper\Events\IpBlockEvent;
use Atlcom\LaravelHelper\Enums\IpBlockRuleEnum;
use Atlcom\LaravelHelper\Services\IpBlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты сервиса блокировки ip адресов
 */
final class IpBlockServiceTest extends PackageTestCase
{
    private string $storageFile;


    protected function setUp(): void
    {
        parent::setUp();

        $this->storageFile = storage_path('framework/testing/ip-block-service-test.json');

        @unlink($this->storageFile);

        Config::set('laravel-helper.ip_block.enabled', true);
        Config::set('laravel-helper.ip_block.storage_file', $this->storageFile);
        Config::set('laravel-helper.ip_block.block_ttl_seconds', 3600);
        Config::set('laravel-helper.ip_block.manual_allow', []);
        Config::set('laravel-helper.ip_block.manual_deny', []);
        Config::set('laravel-helper.ip_block.ignore', []);
        Config::set('laravel-helper.ip_block.trusted_proxies', []);
        Config::set('laravel-helper.ip_block.rules.requests_per_minute.enabled', true);
        Config::set('laravel-helper.ip_block.rules.requests_per_minute.limit', 100);
        Config::set('laravel-helper.ip_block.rules.not_found_per_minute.enabled', true);
        Config::set('laravel-helper.ip_block.rules.not_found_per_minute.limit', 10);
        Config::set('laravel-helper.ip_block.rules.unauthorized_per_minute.enabled', true);
        Config::set('laravel-helper.ip_block.rules.unauthorized_per_minute.limit', 5);
        Config::set('laravel-helper.ip_block.rules.suspicious_payload.enabled', true);
        Config::set('laravel-helper.ip_block.rules.suspicious_payload.patterns', ['(?:<\\s*script\\b)']);
    }


    #[Test]
    public function blocksIpWhenRequestsPerMinuteExceeded(): void
    {
        $service = app(IpBlockService::class);

        for ($i = 0; $i < 101; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '203.0.113.10');
            $service->registerIncomingRequest($request);
        }

        $this->assertTrue($service->isBlockedIp('203.0.113.10'));
    }


    #[Test]
    public function blocksIpWhenSuspiciousPayloadDetected(): void
    {
        $service = app(IpBlockService::class);

        $request = Request::create('/test?q=<script>alert(1)</script>', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.11');

        $service->registerIncomingRequest($request);

        $this->assertTrue($service->isBlockedIp('203.0.113.11'));
    }


    #[Test]
    public function manualAllowHasPriorityOverManualDeny(): void
    {
        Config::set('laravel-helper.ip_block.manual_allow', ['203.0.113.12']);
        Config::set('laravel-helper.ip_block.manual_deny', ['203.0.113.12']);

        $service = app(IpBlockService::class);

        $this->assertFalse($service->isBlockedIp('203.0.113.12'));
    }


    #[Test]
    public function dispatchesIpBlockEventWithBlockMetadata(): void
    {
        Event::fake([IpBlockEvent::class]);

        $service = app(IpBlockService::class);
        $service->blockIp('203.0.113.30', 'manual_reason', 'manual', 'manual description');

        Event::assertDispatched(IpBlockEvent::class, static function (IpBlockEvent $event): bool {
            return $event->dto->ip === '203.0.113.30'
                && $event->dto->reason === 'manual_reason'
                && $event->dto->source === 'manual'
                && $event->dto->description === 'manual description'
                && $event->dto->isBlocked === true
                && $event->dto->blockedAt > 0
                && $event->dto->expiresAt > $event->dto->blockedAt
                && $event->dto->ttl === 3600;
        });
    }


    #[Test]
    public function doesNotDispatchDuplicateEventForAlreadyBlockedIp(): void
    {
        Event::fake([IpBlockEvent::class]);

        $service = app(IpBlockService::class);
        $service->blockIp('203.0.113.13', 'first_reason', 'auto', 'first');
        $service->blockIp('203.0.113.13', 'second_reason', 'auto', 'second');

        Event::assertDispatchedTimes(IpBlockEvent::class, 1);
        $this->assertCount(1, $service->getBlockedIps());
    }


    #[Test]
    public function supportsCidrAndWildcardInManualAllowAndDeny(): void
    {
        Config::set('laravel-helper.ip_block.manual_allow', ['198.51.100.0/24', '203.0.113.*']);
        Config::set('laravel-helper.ip_block.manual_deny', ['198.51.100.10', '203.0.113.15']);

        $service = app(IpBlockService::class);

        $this->assertFalse($service->isBlockedIp('198.51.100.10'));
        $this->assertFalse($service->isBlockedIp('203.0.113.15'));
    }


    #[Test]
    public function supportsCidrAndWildcardInIgnoreList(): void
    {
        Config::set('laravel-helper.ip_block.ignore', ['198.51.101.0/24', '203.0.114.*']);
        Config::set('laravel-helper.ip_block.rules.requests_per_minute.enabled', true);
        Config::set('laravel-helper.ip_block.rules.requests_per_minute.limit', 0);

        $service = app(IpBlockService::class);

        $requestCidr = Request::create('/test', 'GET');
        $requestCidr->server->set('REMOTE_ADDR', '198.51.101.25');
        $service->registerIncomingRequest($requestCidr);

        $requestWildcard = Request::create('/test', 'GET');
        $requestWildcard->server->set('REMOTE_ADDR', '203.0.114.77');
        $service->registerIncomingRequest($requestWildcard);

        $this->assertFalse($service->isBlockedIp('198.51.101.25'));
        $this->assertFalse($service->isBlockedIp('203.0.114.77'));
    }


    #[Test]
    public function doesNotFailWhenStateStorageIsUnavailable(): void
    {
        $parentFile = storage_path('framework/testing/ip-block-parent-file');

        @unlink($parentFile);
        if (!is_dir(dirname($parentFile))) {
            mkdir(dirname($parentFile), 0755, true);
        }
        file_put_contents($parentFile, 'not a directory');

        try {
            Config::set('laravel-helper.ip_block.storage_file', "{$parentFile}/state.php");

            $service = app(IpBlockService::class);
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '203.0.113.40');

            $service->registerIncomingRequest($request);

            $this->assertFalse($service->isBlockedIp('203.0.113.40'));
        } finally {
            @unlink($parentFile);
        }
    }


    #[Test]
    public function storesStateRelativeToCurrentStoragePath(): void
    {
        $relativePath = 'framework/testing/ip-block-relative-state.php';
        $targetPath = storage_path($relativePath);

        @unlink($targetPath);
        Config::set('laravel-helper.ip_block.storage_file', $relativePath);

        try {
            app(IpBlockService::class)->blockIp('203.0.113.41', 'manual_reason', 'manual');

            $this->assertFileExists($targetPath);
        } finally {
            @unlink($targetPath);
        }
    }


    #[Test]
    public function mapsCachedDefaultStoragePathFromAnotherRootToCurrentStoragePath(): void
    {
        $targetPath = storage_path('framework/ip-block-state.php');

        @unlink($targetPath);
        Config::set('laravel-helper.ip_block.storage_file', '/tmp/old-project/storage/framework/ip-block-state.php');

        try {
            app(IpBlockService::class)->blockIp('203.0.113.42', 'manual_reason', 'manual');

            $this->assertFileExists($targetPath);
        } finally {
            @unlink($targetPath);
        }
    }


    #[Test]
    public function updateRulesCanClearSuspiciousPayloadPatterns(): void
    {
        $service = app(IpBlockService::class);

        $service->updateRules([
            IpBlockRuleEnum::SuspiciousPayload->value => [
                'enabled'  => true,
                'patterns' => ['custom-pattern'],
            ],
        ]);

        $this->assertSame(['custom-pattern'], $service->getRules()['rules'][IpBlockRuleEnum::SuspiciousPayload->value]['patterns']);

        $service->updateRules([
            IpBlockRuleEnum::SuspiciousPayload->value => [
                'patterns' => [],
            ],
        ]);

        $this->assertSame([], $service->getRules()['rules'][IpBlockRuleEnum::SuspiciousPayload->value]['patterns']);
    }


    #[Test]
    public function metricsUpdatesKeepRulesWhenStateFileIsTemporarilyUnreadable(): void
    {
        $service = app(IpBlockService::class);

        $service->updateRules([
            IpBlockRuleEnum::RequestsPerMinute->value => [
                'enabled' => true,
                'limit'   => 777,
            ],
            IpBlockRuleEnum::SuspiciousPayload->value => [
                'enabled'  => true,
                'patterns' => ['custom-pattern'],
            ],
        ]);

        file_put_contents($this->storageFile, '');

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.43');

        $service->registerIncomingRequest($request);
        $rules = $service->getRules()['rules'];

        $this->assertSame(777, $rules[IpBlockRuleEnum::RequestsPerMinute->value]['limit']);
        $this->assertSame(['custom-pattern'], $rules[IpBlockRuleEnum::SuspiciousPayload->value]['patterns']);
    }
}
