<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Services\UpdateService;
use Pubvana\Tests\Support\TestCase;

use function mkdir;
use function file_put_contents;
use function json_encode;
use function sys_get_temp_dir;
use function uniqid;
use function rmdir;
use function unlink;

#[CoversClass(UpdateService::class)]
final class UpdateServiceTest extends TestCase
{
    // ------------------------------------------------------------------
    // readManifestVersion
    // ------------------------------------------------------------------

    public function testReadManifestVersionReadsSemver(): void
    {
        $path = $this->tmpFile(['semver' => '3.1.4']);

        self::assertSame('3.1.4', UpdateService::readManifestVersion($path));

        @unlink($path);
    }

    public function testReadManifestVersionFallsBackToVersionKey(): void
    {
        $path = $this->tmpFile(['version' => '9.9.9']);

        self::assertSame('9.9.9', UpdateService::readManifestVersion($path));

        @unlink($path);
    }

    public function testReadManifestVersionReturnsNullForMissingOrBadFiles(): void
    {
        self::assertNull(UpdateService::readManifestVersion('/nonexistent/path/pubvana.json'));

        $path = $this->tmpFile('not-json');
        self::assertNull(UpdateService::readManifestVersion($path));
        @unlink($path);
    }

    // ------------------------------------------------------------------
    // pickTarget
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    private function releases(): array
    {
        return [
            ['version' => '3.0.3', 'min_php_version' => '8.2'],
            ['version' => '3.0.2', 'min_php_version' => '8.2'],
            ['version' => '3.0.1', 'min_php_version' => '8.2'],
        ];
    }

    public function testPickTargetReturnsHighestReleaseAboveCurrent(): void
    {
        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), [], []);

        self::assertSame('3.0.3', $picked['target']['version'] ?? null);
        self::assertNull($picked['capped_by']);
    }

    public function testPickTargetIgnoresReleasesAtOrBelowCurrent(): void
    {
        $picked = UpdateService::pickTarget('3.0.3', $this->releases(), [], []);

        self::assertNull($picked['target']);
    }

    public function testPickTargetSkipsRequestedVersions(): void
    {
        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), [], ['3.0.3']);

        self::assertSame('3.0.2', $picked['target']['version'] ?? null);
    }

    public function testPickTargetCapsByExtensionMax(): void
    {
        $constraints = [
            'pubvana/some-plugin' => ['min' => null, 'max' => '3.0.1'],
        ];

        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), $constraints, []);

        self::assertSame('3.0.1', $picked['target']['version'] ?? null);
        self::assertSame('pubvana/some-plugin', $picked['capped_by']);
    }

    public function testPickTargetBlocksBelowExtensionMin(): void
    {
        $minOnly = [
            'pubvana/some-plugin' => ['min' => '3.0.2', 'max' => null],
        ];

        // A minimum alone never lowers the target: the highest candidate
        // above the floor still wins.
        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), $minOnly, []);
        self::assertSame('3.0.3', $picked['target']['version'] ?? null);

        // Combined with a max, the floor pins the target into the window.
        $window = [
            'pubvana/some-plugin' => ['min' => '3.0.2', 'max' => '3.0.2'],
        ];
        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), $window, []);
        self::assertSame('3.0.2', $picked['target']['version'] ?? null);
        self::assertSame('pubvana/some-plugin', $picked['capped_by']);
    }

    public function testPickTargetReturnsNullWhenAllCandidatesBlocked(): void
    {
        $constraints = [
            'pubvana/some-plugin' => ['min' => null, 'max' => '2.9.0'],
        ];

        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), $constraints, []);

        self::assertNull($picked['target']);
        self::assertSame('pubvana/some-plugin', $picked['capped_by']);
    }

    public function testPickTargetSkippedVersionDoesNotCap(): void
    {
        $picked = UpdateService::pickTarget('3.0.0', $this->releases(), [], ['3.0.3']);

        self::assertNull($picked['capped_by']);
    }

    // ------------------------------------------------------------------
    // scanManifests
    // ------------------------------------------------------------------

    public function testScanManifestsReadsConstraintsAndSkipsUnconstrained(): void
    {
        $root = sys_get_temp_dir() . '/pv-scan-' . uniqid();
        @mkdir($root . '/plugins/One', 0775, true);
        @mkdir($root . '/plugins/Two', 0775, true);
        @mkdir($root . '/themes/ThemeA', 0775, true);

        file_put_contents($root . '/plugins/One/pubvana.json', json_encode([
            'name' => 'pubvana/one',
            'max_pubvana_version' => '3.1.0',
        ]));
        file_put_contents($root . '/plugins/Two/pubvana.json', json_encode([
            'name' => 'pubvana/two',
        ]));
        file_put_contents($root . '/themes/ThemeA/pubvana.json', json_encode([
            'name' => 'pubvana/theme-a',
            'min_pubvana_version' => '3.0.2',
        ]));

        $constraints = UpdateService::scanManifests($root, ['plugins', 'themes']);

        self::assertSame(['min' => null, 'max' => '3.1.0'], $constraints['pubvana/one']);
        self::assertSame(['min' => '3.0.2', 'max' => null], $constraints['pubvana/theme-a']);
        self::assertArrayNotHasKey('pubvana/two', $constraints);

        @unlink($root . '/plugins/One/pubvana.json');
        @unlink($root . '/plugins/Two/pubvana.json');
        @unlink($root . '/themes/ThemeA/pubvana.json');
        @rmdir($root . '/plugins/One');
        @rmdir($root . '/plugins/Two');
        @rmdir($root . '/plugins');
        @rmdir($root . '/themes/ThemeA');
        @rmdir($root . '/themes');
        @rmdir($root);
    }

    // ------------------------------------------------------------------
    // Range helpers
    // ------------------------------------------------------------------

    public function testReleasesBetweenIsInclusiveOfTarget(): void
    {
        $between = UpdateService::releasesBetween($this->releases(), '3.0.0', '3.0.2');

        self::assertSame(['3.0.2', '3.0.1'], array_column($between, 'version'));
    }

    public function testMinPhpBetweenTakesTheStrictestFloor(): void
    {
        $releases = [
            ['version' => '3.1.0', 'min_php_version' => '8.3'],
            ['version' => '3.0.2', 'min_php_version' => '8.2'],
            ['version' => '3.0.1', 'min_php_version' => '8.2'],
        ];

        self::assertSame('8.3', UpdateService::minPhpBetween($releases, '3.0.0', '3.1.0'));
        self::assertSame('8.2', UpdateService::minPhpBetween($releases, '3.0.0', '3.0.2'));
        self::assertNull(UpdateService::minPhpBetween($releases, '3.1.0', '3.1.0'));
    }

    public function testCollectFieldGathersValuesAcrossRange(): void
    {
        $releases = [
            ['version' => '3.0.2', 'breaking_changes' => ['Route X renamed']],
            ['version' => '3.0.1', 'breaking_changes' => ['Config Y removed']],
            ['version' => '3.0.0', 'breaking_changes' => ['Should not appear']],
        ];

        self::assertSame(
            ['Route X renamed', 'Config Y removed'],
            UpdateService::collectField($releases, '3.0.0', '3.0.2', 'breaking_changes')
        );
    }

    public function testPhpVersionSatisfiesHandlesCurrentPhp(): void
    {
        $releases = [['version' => '3.0.1', 'min_php_version' => '5.6']];

        self::assertTrue(UpdateService::phpVersionSatisfies($releases, '3.0.0', '3.0.1'));
        self::assertFalse(UpdateService::phpVersionSatisfies(null, '3.0.0', '3.0.1'));
    }

    // ------------------------------------------------------------------
    // Automatic update chain (no network; feed stubbed via httpGet)
    // ------------------------------------------------------------------

    public function testChainIsNoopWhenUpToDate(): void
    {
        $service = $this->chainService($this->feedBody('0.0.0'));

        $result = $service->runAutoUpdateChain();

        self::assertSame('noop', $result['status']);
        self::assertSame('Up to date. Nothing to do.', $result['message']);
        self::assertNull($result['version']);
    }

    public function testChainIsNoopWhenAutoUpdateDisabled(): void
    {
        $service = $this->chainService($this->feedBody('3.1.0'));

        $result = $service->runAutoUpdateChain();

        self::assertSame('noop', $result['status']);
        self::assertStringContainsString('Automatic updates are off', $result['message']);
        self::assertNull($result['version']);
    }

    public function testChainRefusesBreakingChanges(): void
    {
        $service = $this->chainService(
            $this->feedBody('3.1.0', ['breaking_changes' => ['Route X renamed']]),
            ['Updates.autoUpdate' => true]
        );

        $result = $service->runAutoUpdateChain();

        self::assertSame('refused', $result['status']);
        self::assertSame('3.1.0', $result['version']);
    }

    public function testChainReportsFeedFailureAsError(): void
    {
        $service = $this->chainService(null);

        $result = $service->runAutoUpdateChain();

        self::assertSame('error', $result['status']);
        self::assertStringContainsString('Could not fetch', $result['message']);
        self::assertNull($result['version']);
    }

    // ------------------------------------------------------------------
    // Preflight hard/optional flags
    // ------------------------------------------------------------------

    public function testPreflightMarksRequiredAndOptionalChecks(): void
    {
        $service = $this->chainService($this->feedBody('3.1.0'));

        $checks = $service->preFlight('3.1.0', false);

        $byName = [];
        foreach ($checks as $check) {
            $byName[$check['name']] = $check;
        }

        foreach ($checks as $check) {
            self::assertArrayHasKey('hard', $check);
        }

        self::assertTrue($byName['PHP version']['hard']);
        self::assertTrue($byName['PHP version']['ok']);
        self::assertTrue($byName['Free disk space']['hard']);
        self::assertTrue($byName['Backups plugin']['hard']);
        self::assertFalse($byName['Command line execution']['hard']);
    }

    // ------------------------------------------------------------------
    // Compatibility constraints in check state
    // ------------------------------------------------------------------

    public function testCheckStateCarriesConstraintsWhenCapped(): void
    {
        $scanRoot = sys_get_temp_dir() . '/pv-scan-' . uniqid();
        @mkdir($scanRoot . '/plugins/Blocker', 0775, true);
        file_put_contents($scanRoot . '/plugins/Blocker/pubvana.json', json_encode([
            'name'                => 'pubvana/blocker',
            'max_pubvana_version' => '3.0.9',
        ]));

        $service = $this->chainService(
            $this->feedBody('3.1.0'),
            [],
            $scanRoot
        );

        $state = $service->check(true);

        self::assertSame('up_to_date', $state['status']);
        self::assertSame('pubvana/blocker', $state['capped_by']);
        self::assertSame([['name' => 'pubvana/blocker', 'min' => null, 'max' => '3.0.9']], $state['constraints']);

        @unlink($scanRoot . '/plugins/Blocker/pubvana.json');
        @rmdir($scanRoot . '/plugins/Blocker');
        @rmdir($scanRoot . '/plugins');
        @rmdir($scanRoot);
    }

    public function testCheckStateCarriesConstraintsWhenTargetIsCapped(): void
    {
        $scanRoot = sys_get_temp_dir() . '/pv-scan-' . uniqid();
        @mkdir($scanRoot . '/plugins/Blocker', 0775, true);
        file_put_contents($scanRoot . '/plugins/Blocker/pubvana.json', json_encode([
            'name'                => 'pubvana/blocker',
            'max_pubvana_version' => '3.0.5',
        ]));

        $feed = (string) json_encode(['releases' => [
            ['version' => '3.1.0', 'min_php_version' => '8.2', 'breaking_changes' => [], 'migration_notes' => [], 'notices' => [], 'download_url' => 'https://example.com/3.1.0.zip'],
            ['version' => '3.0.5', 'min_php_version' => '8.2', 'breaking_changes' => [], 'migration_notes' => [], 'notices' => [], 'download_url' => 'https://example.com/3.0.5.zip'],
        ]]);

        $service = $this->chainService($feed, [], $scanRoot);

        $state = $service->check(true);

        self::assertSame('available', $state['status']);
        self::assertSame('3.0.5', $state['target_version']);
        self::assertSame('pubvana/blocker', $state['capped_by']);
        self::assertSame([['name' => 'pubvana/blocker', 'min' => null, 'max' => '3.0.5']], $state['constraints']);

        @unlink($scanRoot . '/plugins/Blocker/pubvana.json');
        @rmdir($scanRoot . '/plugins/Blocker');
        @rmdir($scanRoot . '/plugins');
        @rmdir($scanRoot);
    }

    // ------------------------------------------------------------------
    // Addon inventory
    // ------------------------------------------------------------------

    public function testAddonsInventoryShapesRows(): void
    {
        $themes = new class {
            public function discover(): array
            {
                return [
                    ['display_name' => 'Default', 'semver' => '1.0.0', 'folder' => 'default'],
                ];
            }
        };

        $loader = new class {
            public function discoverLocal(): array
            {
                return [
                    'pubvana/blog'         => ['manifest' => ['display_name' => 'Blog'], 'version' => '1.2.0'],
                    'pubvana/core-blocks'  => ['manifest' => ['display_name' => 'Core Blocks'], 'version' => '1.0.0'],
                ];
            }

            public function discoverVendor(): array
            {
                return [
                    'enlivenapp/flight-sessions' => ['version' => '0.1.0'],
                ];
            }
        };

        $registry = new class {
            public function get(string $type, string $slot): array
            {
                return [
                    'pubvana.blog.recent'                  => ['label' => 'Recent Posts'],
                    'pubvana/core-blocks.text' => ['label' => 'Text'],
                    'pubvana/core-blocks.html' => ['label' => 'HTML'],
                ];
            }
        };

        $app = $this->app([
            'themes'       => fn(): object => $themes,
            'pluginLoader' => fn(): object => $loader,
            'adext'        => fn(): object => $registry,
        ]);

        $service = new UpdateService($app, ['manifest_path' => '/nonexistent']);

        $addons = $service->addons();

        self::assertSame([['name' => 'Default', 'folder' => 'default', 'version' => '1.0.0']], $addons['themes']);
        self::assertSame([
            ['name' => 'Recent Posts', 'updates_with' => 'Blog'],
            ['name' => 'Text', 'updates_with' => 'Core Blocks'],
            ['name' => 'HTML', 'updates_with' => 'Core Blocks'],
        ], $addons['blocks']);
        self::assertSame([
            ['name' => 'enlivenapp/flight-sessions', 'id' => 'enlivenapp/flight-sessions', 'version' => '0.1.0'],
            ['name' => 'Blog', 'id' => 'pubvana/blog', 'version' => '1.2.0'],
            ['name' => 'Core Blocks', 'id' => 'pubvana/core-blocks', 'version' => '1.0.0'],
        ], $addons['plugins']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function tmpFile(mixed $content): string
    {
        $path = sys_get_temp_dir() . '/pv-manifest-' . uniqid() . '.json';
        file_put_contents($path, is_string($content) ? $content : json_encode($content));

        return $path;
    }

    /**
     * A chain-ready service with the release feed stubbed and a settings
     * stand-in. $preset seeds the settings store (e.g. Updates.autoUpdate);
     * $scanRoot points manifest constraint scanning at a fixture directory.
     *
     * @param array<string, mixed> $preset
     */
    private function chainService(?string $feedBody, array $preset = [], ?string $scanRoot = null): UpdateService
    {
        $settings = new class {
            /** @var array<string, mixed> */
            public array $store = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set(string $key, mixed $value): void
            {
                $this->store[$key] = $value;
            }
        };

        foreach ($preset as $key => $value) {
            $settings->set($key, $value);
        }

        $app = $this->app([
            'settings' => fn(): object => $settings,
        ]);

        $config = ['manifest_path' => '/nonexistent/pubvana.json'];
        if ($scanRoot !== null) {
            $config['scan_root'] = $scanRoot;
        }

        return new class ($app, $config, $feedBody) extends UpdateService {
            public function __construct(
                Engine $app,
                array $config,
                private ?string $feedBody,
            ) {
                parent::__construct($app, $config);
            }

            protected function httpGet(string $url, int $timeoutSeconds): ?string
            {
                return $this->feedBody;
            }
        };
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function feedBody(string $version, array $extra = []): string
    {
        $release = array_merge([
            'version'          => $version,
            'release_date'     => '2026-09-04',
            'min_php_version'  => '8.2',
            'breaking_changes' => [],
            'migration_notes'  => [],
            'notices'          => [],
            'download_url'     => 'https://example.com/release.zip',
        ], $extra);

        return (string) json_encode(['releases' => [$release]]);
    }
}
