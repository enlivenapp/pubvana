<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use flight\net\Router;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PluginLoader;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PluginLoader's Shield settings overlay (applyShieldSettingOverrides).
 *
 * The Security admin page stores Shield.* booleans in the settings store;
 * the loader folds them onto the flight-shield config between migrations
 * and plugin registration. These tests drive the protected method through
 * reflection against a real SettingsService on the in-memory database and
 * assert the translated config shape Shield reads at boot.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginLoader::class)]
final class PluginLoaderShieldSettingsTest extends TestCase
{
    private PDO $pdo;

    private Engine $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();

        $this->app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => fn(): object => new class {
                public function get(string $type, string $slot): array
                {
                    return [];
                }
            },
            'settings' => fn(): SettingsService => new SettingsService($this->app),
        ]);
        \Flight::setEngine($this->app);
    }

    public function testStoredTogglesFoldOntoShieldConfig(): void
    {
        $this->storeToggles([
            'Shield.email_2fa' => true,
            'Shield.email_activation' => true,
            'Shield.allow_registration' => false,
            'Shield.magic_link' => true,
            'Shield.remember_me' => false,
        ]);

        $loader = $this->makeLoader();

        self::invoke($loader, 'applyShieldSettingOverrides');

        $config = $this->property($loader, 'enabledPlugins')['enlivenapp/flight-shield'];

        self::assertSame(
            \Enlivenapp\FlightShield\Authentication\Actions\Email2FA::class,
            $config['actions']['login']
        );
        self::assertSame(
            \Enlivenapp\FlightShield\Authentication\Actions\EmailActivator::class,
            $config['actions']['register']
        );
        self::assertFalse($config['allow_registration']);
        self::assertTrue($config['allow_magic_link']);
        self::assertFalse($config['session']['allow_remembering']);
    }

    public function testDisabledTogglesStoreNullActionClasses(): void
    {
        $this->storeToggles([
            'Shield.email_2fa' => false,
            'Shield.email_activation' => false,
        ]);

        $loader = $this->makeLoader();
        self::invoke($loader, 'applyShieldSettingOverrides');

        $config = $this->property($loader, 'enabledPlugins')['enlivenapp/flight-shield'];

        self::assertNull($config['actions']['login']);
        self::assertNull($config['actions']['register']);
    }

    public function testNoStoredRowsLeavesConfigUntouched(): void
    {
        $loader = $this->makeLoader();
        self::invoke($loader, 'applyShieldSettingOverrides');

        $config = $this->property($loader, 'enabledPlugins')['enlivenapp/flight-shield'];

        self::assertArrayNotHasKey('actions', $config);
        self::assertArrayNotHasKey('allow_registration', $config);
    }

    public function testMissingShieldKeyIsNoOp(): void
    {
        $this->storeToggles(['Shield.email_2fa' => true]);

        $loader = new PluginLoader(
            $this->app,
            $this->app->router(),
            '/tmp/does-not-exist-plugins',
            '/tmp/does-not-exist-vendor',
            []
        );

        self::invoke($loader, 'applyShieldSettingOverrides');

        self::assertArrayNotHasKey('enlivenapp/flight-shield', $this->property($loader, 'enabledPlugins'));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeLoader(): PluginLoader
    {
        return new PluginLoader(
            $this->app,
            $this->router(),
            '/tmp/does-not-exist-plugins',
            '/tmp/does-not-exist-vendor',
            ['enlivenapp/flight-shield' => ['enabled' => true, 'priority' => 5]]
        );
    }

    private function router(): Router
    {
        /** @var Router $router */
        $router = $this->app->router();

        return $router;
    }

    /**
     * @param array<string, bool> $toggles
     */
    private function storeToggles(array $toggles): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (key, value, type, autoload, created_at, updated_at) '
            . 'VALUES (:k, :v, :t, 1, :now, :now)'
        );
        $now = date('Y-m-d H:i:s');

        foreach ($toggles as $key => $value) {
            $stmt->execute([
                ':k' => $key,
                ':v' => $value ? '1' : '0',
                ':t' => 'boolean',
                ':now' => $now,
            ]);
        }
    }
}
