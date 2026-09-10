<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

#[CoversClass(MarketplaceService::class)]
final class MarketplaceServiceTest extends TestCase
{
    private SettingsStub $settings;
    private TestMarketplaceService $service;

    protected function setUp(): void
    {
        $this->settings = new SettingsStub();
        $app = $this->app([
            'settings' => fn (): SettingsStub => $this->settings,
        ]);
        $this->service = new TestMarketplaceService(
            Sqlite::recreate(),
            $app,
            ['store_url' => 'http://localhost', 'api_timeout' => 3],
        );
    }

    public function testConnectAccountRejectsInvalidEmailWithoutCallingStore(): void
    {
        $result = $this->service->connectAccount('not-an-email', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountRequiresPassword(): void
    {
        $result = $this->service->connectAccount('you@example.com', '', 'secret123');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
    }

    public function testConnectAccountRequiresMatchingConfirmation(): void
    {
        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret124');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
    }

    public function testConnectAccountSendsEmailPasswordAndPasswordConf(): void
    {
        $this->service->responses = ['{"ok":true,"token":"abc:xyz"}'];

        $result = $this->service->connectAccount('You@Example.com', 'secret123', 'secret123');

        self::assertTrue($result['ok']);
        self::assertCount(1, $this->service->sent);
        $sent = $this->service->sent[0];
        self::assertSame('http://localhost/api/store/auth/token', $sent['url']);
        self::assertSame('you@example.com', $sent['payload']['email']);
        self::assertSame('secret123', $sent['payload']['password']);
        self::assertSame('secret123', $sent['payload']['password_conf']);
    }

    public function testConnectAccountStoresTokenOnSuccess(): void
    {
        $this->service->responses = ['{"ok":true,"token":"abc:xyz"}'];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertTrue($result['ok']);
        self::assertSame('abc:xyz', $this->settings->data['Marketplace.account_token']);
        self::assertSame('you@example.com', $this->settings->data['Marketplace.account_email']);
        self::assertTrue($this->service->connected());
    }

    public function testConnectAccountSurfacesStoreReason(): void
    {
        $this->service->responses = ['{"ok":false,"reason":"The email or password is incorrect. Reset your password at the store if you forgot it."}'];

        $result = $this->service->connectAccount('you@example.com', 'wrong', 'wrong');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('incorrect', (string) $result['reason']);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountSurfacesActivationRequired(): void
    {
        $this->service->responses = ['{"ok":true,"activation_required":true}'];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('activation', (string) $result['reason']);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountReportsUnreachableStore(): void
    {
        $this->service->responses = [null];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('could not be reached', (string) $result['reason']);
    }

    public function testItemsCarryLicenseScopeForDisclosure(): void
    {
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';
        $this->service->getResponses = [
            '{"ok":true,"items":[{"id":7,"name":"Demo","is_free":false,"license_scope":"single_site","price":19.0,"currency":"USD","item_type":"plugin"}]}',
        ];

        $items = $this->service->items('USD');

        self::assertCount(1, $items);
        self::assertSame('single_site', $items[0]['license_scope']);
        self::assertFalse($items[0]['is_free']);
    }
}

/**
 * Lightweight settings stand-in backed by an in-memory array.
 */
final class SettingsStub
{
    /** @var array<string, mixed> */
    public array $data = [];

    public function get(string $key, mixed $default = ''): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}

/**
 * MarketplaceService with HTTP methods stubbed so tests never hit the network.
 */
final class TestMarketplaceService extends MarketplaceService
{
    /** @var list<array{url: string, payload: array<string, mixed>}> */
    public array $sent = [];

    /** @var list<?string> */
    public array $responses = [];

    /** @var list<?string> */
    public array $getResponses = [];

    protected function httpPostJson(string $url, array $payload): ?string
    {
        $this->sent[] = ['url' => $url, 'payload' => $payload];
        return array_shift($this->responses);
    }

    protected function httpGet(string $url): ?string
    {
        return array_shift($this->getResponses);
    }

    protected function userAgent(): string
    {
        return 'Pubvana-Marketplace-Test';
    }
}